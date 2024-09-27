<?php

namespace App\Services;

use App\Contracts\ICardProcessor;
use App\Models\AddedItem;
use App\Models\CurrentQuantity;
use App\Models\Discount;
use App\Models\ItemSpecificDiscount;
use App\Models\PosOrder;
use App\Models\Preference;
use App\Models\Quantity;
use App\Models\Store;
use App\Models\GiftCard;
use App\Models\GiftCardTopUp;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Money\Money;

class PosOrderService
{
    private $processor;

    public function __construct(ICardProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function createOrder($newOrder, $request)
    {
        $preferences = Preference::
            where('organization_id', $newOrder['organization_id'])
            ->first();
        
        $posOrder = new PosOrder;

        try
        {
            DB::beginTransaction();

            $posOrder->fill($newOrder);
            if ($request->giftCode != null) {
                $posOrder->gift_card_id = $request->giftCardId;
            }
            $posOrder->save();

            if($request->giftCode != null) {
                // update the gift card balance
                $giftCard = GiftCard::where('gift_code', $request->giftCode)->firstOrFail();
                $giftCard->update([
                    'balance' => $request->giftCardRemainingBalance,
                ]);

                // insert data in gift card top ups
                $giftCardTopUpData = [
                    "amount" => $request->giftCardAmount,
                    "action" => 2,
                    "gift_card_id" => $request->giftCardId,
                ];
                $giftCardTopUp = new GiftCardTopUp($giftCardTopUpData);
                $giftCardTopUp->save();
            }

            $items = self::mapItems($newOrder['items'], $posOrder, $preferences->classifications_disabled);
            $posOrder->posOrderItems()->createMany($items);

            if ($newOrder['card'] > 0 && $preferences->using_merchant_partner)
            {
                $transactionResponse = $this->processor->authorizeTerminalTransaction(
                    $preferences->merchant_id, 
                    $newOrder['terminal_hsn'],
                    $newOrder['card'], 
                    $newOrder['is_debit']
                );

                $posOrder->processor_reference = $transactionResponse['reference_id'];
                $posOrder->save();
                
                if (isset($transactionResponse['processing_details']))
                {
                    $posOrder->processing_details = $transactionResponse['processing_details'];
                }
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            throw $e;
        }

        return $posOrder;
    }

    public function getOrder(int $id)
    {
        $order = PosOrder::with("posReturnItems")->findOrFail($id);

        if ($order->processor_reference)
        {
            $transaction = $this->processor->getTransaction(
                $order->preferences->merchant_id, 
                $order->processor_reference
            );

            if (isset($transaction['emvTagData']))
            {
                $emvTagData = $transaction['emvTagData'];
                $order->processing_details = [
                    'auth_code' => $transaction['authcode'],
                    'mode' => $emvTagData['Mode'],
                    'entry_method' => $emvTagData['Entry method'],
                    'card_label' => $emvTagData['Application Label']
                ];
            }
        }

        return $order;
    }

    public function getOrderForReturnById(int $id)
    {
        $sqlStatement = "
        SELECT *
        FROM
            (
                SELECT o.*,
                    (
                        o.cash - 
                        (
                            SELECT COALESCE(SUM(r.cash), 0)
                            FROM pos_orders AS o
                            INNER JOIN pos_returns AS r
                            ON o.id=r.pos_order_id
                            WHERE o.id=?
                        )
                    ) AS cash_left,
                    (
                        o.card - 
                        (
                            SELECT COALESCE(SUM(r.card), 0)
                            FROM pos_orders AS o
                            INNER JOIN pos_returns AS r
                            ON o.id=r.pos_order_id
                            WHERE o.id=?
                        )
                    ) AS card_left,
                    (
                        o.ebt - 
                        (
                            SELECT COALESCE(SUM(r.ebt), 0)
                            FROM pos_orders AS o
                            INNER JOIN pos_returns AS r
                            ON o.id=r.pos_order_id
                            WHERE o.id=?
                        )
                    ) AS ebt_left
                FROM pos_orders AS o
                WHERE o.id=?
            ) AS subquery;
        ";
        $query = DB::select($sqlStatement, [$id, $id, $id, $id]);

        if (count($query) == 0)
        {
            throw new ModelNotFoundException;
        }
        
        $order = PosOrder::hydrate($query)->first();
        $order->posOrderItems;
        $order->posReturnItems;

        return $order;
    }

    public static function calculateTotals(
        int $storeId, 
        Collection $items, 
        bool $isTaxed,
        bool $isEbt,
        int $discountAmount=null
    )
    {
        $store = Store::find($storeId);
        $classificationsDisabled = $store->preferences->classifications_disabled;

        $taxableSubTotal = Money::USD(0);
        $ebtSubTotal = Money::USD(0);
        $nonTaxedSubTotal = Money::USD(0);

        $totals = collect();
        foreach ($items as $item) 
        {
            $qtyOrdered = $item['quantity_ordered'];
            $price = $item['price'];
            $itemDiscountAmount = Money::USD(0);
            $itemDiscount = ItemSpecificDiscount::where('item_id', $item['id'])
                ->where(function (Builder $query) {
                    $query->whereDate('active_at', '<=', now())
                        ->orWhereNull('active_at')
                        ->whereDate('expires_at', '>=', now())
                        ->orWhere(function (Builder $query) {
                            $query->whereNull('active_at')
                                ->whereNull('expires_at');
                        });
                })
                ->first();
            $discountString = null;
            $total = Money::USD($price)->multiply($qtyOrdered);

            if ($itemDiscount)
            {
                $possibleUses = intval($qtyOrdered / $itemDiscount->quantity);
                
                if ($possibleUses > 0)
                {
                    $discountOriginalAmount = $itemDiscount->discount_amount;
                    $discountString = "Buy " . $itemDiscount->quantity . ' for ';
                    $validUses = isset($itemDiscount->times_applicable) && $possibleUses > $itemDiscount->times_applicable 
                        ? $itemDiscount->times_applicable 
                        : $possibleUses;

                    if ($itemDiscount->discount_type == "amount")
                    {
                        if (isset($item['discount_id']))
                        {
                            $discountToStack = Discount::find($item['discount_id']);
                            $itemDiscount->discount_amount *= 1 - $discountToStack->discount;
                        }

                        $baseToDiscount = $price * $itemDiscount->quantity * $validUses;
                        $itemDiscountAmount = Money::USD($baseToDiscount)->subtract(Money::USD($itemDiscount->discount_amount * $validUses));
                        // var_dump($discountOriginalAmount , $itemDiscount->discount_amount, $discountOriginalAmount != $itemDiscount->discount_amount);
                        $discountString .= '$' . ($discountOriginalAmount != $itemDiscount->discount_amount ? intval($itemDiscount->discount_amount) / 100 . ' (was $' . intval($discountOriginalAmount) / 100 . ')' : intval($discountOriginalAmount) / 100);
                    }
                    else
                    {
                        $itemDiscountAmount = Money::USD($price)->multiply($itemDiscount->discount_amount)->multiply($validUses * $itemDiscount->quantity);
                        $discountString .= $itemDiscount->discount_amount * 100 . '% off';
                    }

                    if ($validUses > 1) $discountString .= ' x' . $validUses;
                }
            }

            $total = $total->subtract($itemDiscountAmount);
            $itemDiscountApplied = isset($itemDiscount) && isset($validUses);
            $itemArr = [
                'id' => $item['id'],
                'price' => (int) $total->getAmount(),
                'discount_description' => $discountString,
                'item_specific_discount_id' => $itemDiscountApplied
                    ? $itemDiscount->id
                    : null,
                'item_specific_discount_quantity' => $itemDiscountApplied
                    ? $itemDiscount->quantity
                    : null,
                'item_specific_discount_original_amount' => $itemDiscountApplied
                    ? $discountOriginalAmount
                    : null,
                'item_specific_discount_amount' => $itemDiscountApplied
                    ? $itemDiscount->discount_amount
                    : null,
                'item_specific_discount_type' => $itemDiscountApplied
                    ? $itemDiscount->discount_type
                    : null,
                'item_specific_discount_times_applied' => $itemDiscountApplied
                    ? $validUses
                    : null,
                'item_specific_discount_can_stack' => $itemDiscountApplied
                    ? $itemDiscount->can_stack
                    : null,
                'item_specific_discount_active_at' => $itemDiscountApplied
                    ? $itemDiscount->active_at
                    : null,
                'item_specific_discount_expires_at' => $itemDiscountApplied
                    ? $itemDiscount->expires_at
                    : null
            ];

            $ebtOption = null;
            $isTaxedOption = null;
            if (!$classificationsDisabled)
            {
                $classification = getClassification($item);
                $ebtOption = $classification->preferenceOptions()
                    ->where([
                        [
                            'store_id', $storeId
                        ],
                        [
                            'key', 'is_ebt'
                        ]
                    ])
                    ->get()
                    ->first();
                $isTaxedOption = $classification->preferenceOptions()
                    ->where([
                        [
                            'store_id', $storeId
                        ],
                        [
                            'key', 'is_taxed'
                        ]
                    ])
                    ->get()
                    ->first();
            }

            if ($isEbt && $ebtOption && $ebtOption->value)
            {
                $itemArr['is_taxed'] = false;
                $ebtSubTotal = $ebtSubTotal->add($total);
            }
            else if ($isTaxedOption && !$isTaxedOption->value)
            {
                $itemArr['is_taxed'] = false;
                $nonTaxedSubTotal = $nonTaxedSubTotal->add($total);
            }
            else
            {
                $itemArr['is_taxed'] = true;
                $taxableSubTotal = $taxableSubTotal->add($total);
            }

            $totals->push($itemArr);
        };

        $ebtEligible = !$ebtSubTotal->isZero();

        $subTotal = $taxableSubTotal->add($ebtSubTotal, $nonTaxedSubTotal);
        $discountAmount = Money::USD($discountAmount);
        $priorSubTotal = $subTotal->add($discountAmount);
        $tax = $isTaxed ? $taxableSubTotal->multiply($store->tax_rate) : Money::USD(0);
        $total = $subTotal->add($tax);

        $orderTotals = [
            'item_totals' => [
                'totals' => $totals->all(),
                'sub_total' => (int) $subTotal->getAmount()
            ],
            'sub_total' => (int) $subTotal->getAmount(),
            'prior_sub_total' => (int) $priorSubTotal->getAmount(),
            'taxable_sub_total' => (int) $taxableSubTotal->getAmount(),
            'ebt_sub_total' => (int) $ebtSubTotal->getAmount(),
            'non_taxed_sub_total' => (int) $nonTaxedSubTotal->getAmount(),
            'all_non_taxed_sub_total' => (int) $nonTaxedSubTotal->add($ebtSubTotal)->getAmount(),
            'tax' => (int) $tax->getAmount(),
            'total' => (int) $total->getAmount(),
            'ebt_eligible' => $ebtEligible
        ];

        return $orderTotals;
    }

    private static function mapItems(array $items, PosOrder $posOrder, bool $classificationsDisabled) {
        return array_map(function($i) use ($posOrder, $classificationsDisabled) {
            $addedItem = isset($i['added_item']) && $i['added_item'];
            $ebtOrder = $posOrder->ebt > 0;
            $storeId = $posOrder->store_id;

            $isEbt = false;
            $isTaxed = true;
            if (!$classificationsDisabled)
            {
                $classification = getClassification($i);
                $isEbt = $ebtOrder && $classification->isEbt($storeId);
                $isTaxed = $isEbt || (isset($i['is_taxed']) && !$i['is_taxed']) || (isset($classification) && !$classification->isTaxed($storeId)) ? false : true;
            }

            if (isset($i['discount_id']) && !is_null($i['discount_id'])) 
            {
                $discount = Discount::find($i['discount_id']);
            }

            if (!$addedItem)
            {
                $qty = CurrentQuantity::where('store_id', $storeId)->where('item_id', $i['id'])->first();
                
                if ($qty)
                {
                    $qtyDifference = $qty->quantity - $i['quantity_ordered'];
                    if ($qtyDifference < 0)
                    {
                        Quantity::create([
                            'item_id' => $i['id'],
                            'store_id' => $storeId,
                            'created_by' => $posOrder->created_by,
                            'quantity_received' => abs($qtyDifference),
                            'message' => 'Added at POS'
                        ]);
                    }
                }
                else 
                {
                    Quantity::create([
                        'item_id' => $i['id'],
                        'store_id' => $storeId,
                        'created_by' => $posOrder->created_by,
                        'quantity_received' => $i['quantity_ordered'],
                        'message' => 'Added at POS'
                    ]);
                }
            }

            $item = [
                'quantity_ordered' => $i['quantity_ordered'],
                'price' => $i['price'],
                'item_id' => $addedItem ? null : $i['id'],
                'item_specific_discount_id' => isset($i['item_specific_discount_id']) ? $i['item_specific_discount_id'] : null,
                'item_specific_discount_quantity' => isset($i['item_specific_discount_quantity']) ? $i['item_specific_discount_quantity'] : null,
                'item_specific_discount_times_applied' => isset($i['item_specific_discount_times_applied']) ? $i['item_specific_discount_times_applied'] : null,
                'item_specific_discount_can_stack' => isset($i['item_specific_discount_can_stack']) ? $i['item_specific_discount_can_stack'] : null,
                'item_specific_discount_original_amount' => isset($i['item_specific_discount_original_amount']) ? $i['item_specific_discount_original_amount'] : null,
                'item_specific_discount_amount' => isset($i['item_specific_discount_amount']) ? $i['item_specific_discount_amount'] : null,
                'item_specific_discount_type' => isset($i['item_specific_discount_type']) ? $i['item_specific_discount_type'] : null,
                'item_specific_discount_active_at' => isset($i['item_specific_discount_active_at']) ? Carbon::parse($i['item_specific_discount_active_at'])->toDateString() : null,
                'item_specific_discount_expires_at' => isset($i['item_specific_discount_expires_at']) ? Carbon::parse($i['item_specific_discount_expires_at'])->toDateString() : null,
                'discount_id' => isset($discount) ? $i['discount_id'] : null,
                'discount_percent' => isset($discount) ? $discount->discount : null,
                'discount_amount' => isset($i['discount_amount']) ? $i['discount_amount'] : null,
                'discount_amount_type' => isset($i['discount_amount_type']) ? $i['discount_amount_type'] : null,
                'is_ebt' => $isEbt,
                'is_taxed' => $isTaxed,
                'consignment_fee' => isset($i['consignment_fee']) ? $i['consignment_fee'] : null,
            ];

            if ($addedItem) 
            {
                $i['created_by'] = $posOrder->created_by;
                $i['organization_id'] = $posOrder->organization_id;
                $i['price'] = $i['price'];
                $i['original_price'] = $i['temp_price'] ? $i['temp_price'] : $i['price'];
                
                $addedItem = new AddedItem($i);
                $addedItem->save();
                $item['added_item_id'] = $addedItem->id;
            }
            
            return $item;
        }, $items);
    }
}