<?php

namespace App\Services;

use App\Http\Resources\PosOrder as PosOrderResource;
use App\Models\AddedItem;
use App\Models\Classification;
use App\Models\Condition;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\PosOrder;
use App\Models\PosReturn;
use App\Models\PreferenceOption;
use App\Models\ReceiptOption;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SeedService
{
    private $user;
    private $preferences;
    private $store;
    private $posOrderService;

    public function __construct(PosOrderService $posOrderService)
    {
        try
        {
            $this->user = User::where("username", "TestUser")->firstOrFail();
        }
        catch (Exception $e) 
        {
            return response()->error("User does not exist try running api/test/seed-test-user and trying again.");
        }

        $this->preferences = $this->user->preferences;
        $this->store = $this->preferences->stores()->first();
        $this->posOrderService = $posOrderService;
    }

    public function seedCreateOrderData()
    {
        $this->preferences->using_merchant_partner = false;
        $this->preferences->save();

        $consignmentItem = Item::factory([
            "created_by" => $this->user->id,
            "organization_id" => $this->user->organization_id,
            "classification_id" => $this->preferences->classifications()->first()->id,
            "condition_id" => $this->preferences->conditions->first()->id,
            "title" => "Cypress Test Item",
            "original_price" => 7500,
            "price" => 5000,
            "cost" => 2000,
            "merchant_name" => "Test Merchant",
            "merchant_price" => 7500,
            "consignor_id" => \App\Models\Consignor::factory(),
            "consignment_fee" => 1000
        ])->create();
        $consignmentItem->quantities()->create([
            "created_by" => $this->user->id,
            "store_id" => $this->store->id,
            "quantity_received" => 1,
            "message" => "Cypress test quantity."
        ]);

        $items = collect([
            $this->createBaseItem(), 
            $this->createBaseItem($this->preferences->conditions->last()->id),
            $consignmentItem,
            $this->createEbtItem()]);

        return response()->success(["skus" => $items->pluck("sku")]);
    }

    public function seedPaymentPartnerOrderData()
    {
        $this->preferences->using_merchant_partner = true;
        $this->preferences->checkoutStations()->forceDelete();
        $this->preferences->checkoutStations()->createMany([
            [
                'store_id' => $this->store->id,
                'name' => 'Test Station',
                'terminal' => '18163PP84176695'
            ],
            [
                'store_id' => $this->store->id,
                'name' => 'Test Station 2',
                'terminal' => null
            ]
        ]);
        $this->preferences->save();

        return response()->success();
    }

    public function seedCreateReturnData()
    {
        $this->preferences->using_merchant_partner = false;
        $this->preferences->save();
        
        $items = [
            $this->createBaseItem(),
            $this->createEbtItem()
        ];

        $addedItem = AddedItem::factory([
            "created_by" => $this->user->id,
            "organization_id" => $this->user->organization_id,
            "classification_id" => $this->preferences->classifications()->first()->id,
            "price" => 1000,
            "title" => "Added Item"
        ])->make();
        $addedItem['id'] = "addedItem_1";
        $addedItem['temp_price'] = 1000;
        $addedItem['added_item'] = true;

        $allItems = collect();
        foreach (array_merge($items, [$addedItem]) as $item)
        {
            $dupeItem = $item->toArray();
            $dupeItem['price'] = $item->price;
            $dupeItem['quantity_ordered'] = 1;

            $allItems->push($dupeItem);
        }
        
        $totals = $this->posOrderService->calculateTotals($this->store->id, $allItems, true, true);

        $posOrder = $this->posOrderService->createOrder([
            "created_by" => $this->user->id,
            "organization_id" => $this->user->organization_id,
            "store_id" => $this->store->id,
            "cash" => 0,
            "card" => $totals["taxable_sub_total"],
            "ebt" => $totals["ebt_sub_total"],
            "sub_total" => $totals["sub_total"],
            "tax" => $totals["tax"],
            "total" => $totals["total"],
            "amount_paid" => $totals["total"],
            "change" => 0,
            "tax_rate" => $this->store->tax_rate,
            "items" => $allItems->toArray()
        ]);

        return response()->success([
            "items" => $allItems,
            "order" => new PosOrderResource($posOrder),
            "totals" => $totals
        ]);
    }

    public function createBaseItem(int $conditionId=null)
    {
        $item = Item::factory([
            "created_by" => $this->user->id,
            "organization_id" => $this->user->organization_id,
            "classification_id" => $this->preferences->classifications()->first()->id,
            "condition_id" => $conditionId
                ? $conditionId
                : $this->preferences->conditions->first()->id,
            "title" => "Cypress Test Item",
            "original_price" => 7500,
            "price" => 5000,
            "cost" => 2000,
            "merchant_name" => "Test Merchant",
            "merchant_price" => 7500,
            "upc" => "111222333444"
        ])->create();

        $item->quantities()->create([
            "created_by" => $this->user->id,
            "store_id" => $this->store->id,
            "quantity_received" => 1,
            "message" => "Cypress test quantity."
        ]);

        return $item;
    }

    public function createEbtItem()
    {
        $item = Item::factory([
            "created_by" => $this->user->id,
            "organization_id" => $this->user->organization_id,
            "classification_id" => $this->preferences->classifications()
                ->where("name", "Grocery")
                ->first()
                ->id,
            "condition_id" => $this->preferences->conditions->first()->id,
            "title" => "Cypress EBT Item",
            "original_price" => 2500,
            "price" => 2000,
            "cost" => 500,
            "merchant_name" => "Test Merchant",
            "merchant_price" => 2500,
            "upc" => "444333222111",
        ])->create();

        $item->quantities()->create([
            "created_by" => $this->user->id,
            "store_id" => $this->store->id,
            "quantity_received" => 1,
            "message" => "Cypress test quantity."
        ]);

        return $item;
    }

    public static function clearTestUserData()
    {
        $user = User::where("username", "TestUser")->firstOrFail();

        $orgId = $user->organization_id;
        $preferenceId = $user->preferences->id;

        Manifest::where("organization_id", $orgId)->forceDelete();
        ManifestItem::where("organization_id", $orgId)->forceDelete();

        $posReturns = PosReturn::where("organization_id", $orgId)->get();
        $posReturns->each(function(PosReturn $return) {
            $return->posReturnItems()->forceDelete();
            $return->forceDelete();
        });

        $posOrders = PosOrder::where("organization_id", $orgId)->get();
        $posOrders->each(function(PosOrder $order) {
            $order->posOrderItems()->forceDelete();
            $order->forceDelete();
        });

        AddedItem::where("organization_id", $orgId)->forceDelete();

        $items = Item::where("organization_id", $orgId)->get();
        $items->each(function(Item $item) {
            $item->quantities()->forceDelete();
            $item->itemImages()->forceDelete();
            $item->forceDelete();
        });

        PreferenceOption::where("model_type", "contains", "Classification")
            ->whereIn("id", $user->preferences->classifications()->get()->pluck('id')->toArray())
            ->forceDelete();

        $classifications = Classification::where("preference_id", $preferenceId)->get()->slice(4);
        Classification::whereIn('id', $classifications->modelKeys())->forceDelete();

        $conditions = Condition::where("preference_id", $preferenceId)->get()->slice(4);
        Condition::whereIn('id', $conditions->modelKeys())->forceDelete();

        $discounts = Discount::where("preference_id", $preferenceId)->get()->slice(1);
        Discount::whereIn('id', $discounts->modelKeys())->forceDelete();

        $stores = Store::where("preference_id", $preferenceId)->get()->slice(3);
        Store::whereIn('id', $stores->modelKeys())->forceDelete();

        $receiptOptions = ReceiptOption::where("preference_id", $preferenceId)->get()->slice(1);
        ReceiptOption::whereIn('id', $receiptOptions->modelKeys())->forceDelete();

        return;
    }
}