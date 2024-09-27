<?php

namespace App\Services;

use App\Contracts\ICardProcessor;
use App\Models\PosOrder;
use App\Models\GiftCard;
use App\Models\GiftCardTopUp;
use Exception;
use Illuminate\Support\Facades\DB;
use Money\Money;
use App\Models\PosReturn;
use App\Models\Preference;

class PosReturnService
{
    private $processor;

    public function __construct(ICardProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function createReturn($newReturn, $request)
    {
        $preferences = Preference::
            where('organization_id', $newReturn['organization_id'])
            ->first();

        $posReturn = new PosReturn;

        try
        {
            DB::beginTransaction();

            $posReturn->fill($newReturn);
            $posReturn->save();
            $posReturn->posReturnItems()->createMany($newReturn['items']);

            $cardAmount = (int) $posReturn->card;
            if ($posReturn->posOrder->processor_reference &&  $cardAmount > 0) {
                $transactionResponse = $this->processor->refundTransaction(
                    $preferences->merchant_id,
                    $posReturn->posOrder->processor_reference,
                    $cardAmount
                );

                if ($transactionResponse['respstat'] != 'A')
                {
                    throw new Exception;
                }
            }

            if($request->giftCardId != null) {
                // update the gift card balance
                $updatedBalance = $request->giftCardBalance + $request->gc;
                $giftCard = GiftCard::where('id', $request->giftCardId)->firstOrFail();
                $giftCard->update([
                    'balance' => $updatedBalance,
                ]);

                // insert data in gift card top ups
                $giftCardTopUpData = [
                    "amount" => $request->gc,
                    "action" => 3,
                    "gift_card_id" => $request->giftCardId,
                ];
                $giftCardTopUp = new GiftCardTopUp($giftCardTopUpData);
                $giftCardTopUp->save();
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            throw $e;
        }

        return $posReturn;
    }

    public static function calculateRefund($orderId, $items)
    {
        $order = PosOrder::find($orderId);

        $taxableSubTotal = Money::USD(0);
        $ebtSubTotal = Money::USD(0);
        $nonTaxedSubTotal = Money::USD(0);

        foreach ($items as $item)
        {
            $total = Money::USD($item['price'])->multiply($item['quantity_returned']);

            if (isset($item['item_specific_discount_id']) && $item['quantity_left_to_return'] >= $item['item_specific_discount_quantity'])
            {
                $fullPriceQuantity = $item['quantity_left_to_return'] - ($item['item_specific_discount_quantity'] * $item['item_specific_discount_times_applied']);
                $validUses = intval(($item['quantity_returned'] - $fullPriceQuantity) / $item['item_specific_discount_quantity']);

                if ($fullPriceQuantity - $item['quantity_returned'] < 0)
                {
                    $fullPriceTotal = Money::USD($item['price'])->multiply($fullPriceQuantity);
                    $quantityOutliers = ($item['quantity_returned'] - $fullPriceQuantity) % $item['item_specific_discount_quantity'];
                    
                    if ($item['item_specific_discount_type'] == 'amount')
                    {
                        $discountAmount = Money::USD($item['item_specific_discount_amount']);
    
                        if ($quantityOutliers == 0)
                        {
                            $total = $fullPriceTotal->add($discountAmount->multiply($validUses));
                        } else
                        {
                            $originalPrice = Money::USD($item['price'])->multiply($item['item_specific_discount_quantity'] - $quantityOutliers);
                            $total = $fullPriceTotal->add($discountAmount->multiply($validUses + 1)->subtract($originalPrice));
                        }
                    } else
                    {
                        $discountAmount = Money::USD($item['price'])->multiply($item['item_specific_discount_quantity'])->multiply(1 - $item['item_specific_discount_amount']);
    
                        if ($quantityOutliers == 0)
                        {
                            $total = $fullPriceTotal->add($discountAmount->multiply($validUses));
                        } else
                        {
                            $originalPrice = Money::USD($item['price'])->multiply($item['item_specific_discount_quantity'] - $quantityOutliers);
                            $total = $fullPriceTotal->add($discountAmount->multiply($validUses + 1)->subtract($originalPrice));
                        }
                    }
                }

            }
            
            if ($item['is_ebt']) $ebtSubTotal = $ebtSubTotal->add($total);
            else if (!$item['is_taxed']) $nonTaxedSubTotal = $nonTaxedSubTotal->add($total);
            else $taxableSubTotal = $taxableSubTotal->add($total);
        }

        $tax = $taxableSubTotal->multiply($order->tax_rate);
        $subTotal = $taxableSubTotal->add($ebtSubTotal, $nonTaxedSubTotal);
        $total = $subTotal->add($tax);

        return [
            'sub_total' => (int) $subTotal->getAmount(),
            'taxable_sub_total' => (int) $taxableSubTotal->getAmount(),
            'ebt_sub_total' => (int) $ebtSubTotal->getAmount(),
            'non_taxed_sub_total' => (int) $nonTaxedSubTotal->getAmount(),
            'all_non_taxed_sub_total' => (int) $nonTaxedSubTotal->add($ebtSubTotal)->getAmount(),
            'tax' => (int) $tax->getAmount(),
            'total' => (int) $total->getAmount()
        ];
    }
}