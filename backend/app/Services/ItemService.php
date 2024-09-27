<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use App\Models\ItemHistory;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ItemService
{
    public function createItem(array $itemData, User $user)
    {
        $orgId = $user->organization_id;

        if (!isset($itemData['sku']))
        {
            $itemData['sku'] = $this->getSku($orgId);
        }
        
        
        $itemData['title'] = Str::limit($itemData['title'], 200, '');
        if (isset($itemData['description']) && !is_null($itemData['description']))
        {
            $itemData['description'] = Str::limit($itemData['description'], 1997);
        }
        
        $item = new Item;
        $item->fill($itemData);
        $item->organization_id = $orgId;
        
        if (is_null($item->created_by)) 
        {
            $item->created_by = $user->id;

            foreach ($itemData['quantities'] as &$quantity)
            {
                $quantity['created_by'] = $user->id;
            }
        }
        
        try
        {
            DB::beginTransaction();

            $item->save();
            $item->quantities()->createMany($itemData['quantities']);
            // Images are not required
            if (isset($itemData['images']) && count($itemData['images']) > 0)
            {
                $images = array_map(function ($image) {
                    return ['image_url' => $image];
                }, $itemData['images']);
                
                $item->itemImages()->createMany($images);
            }
            if (isset($itemData['specific_discounts']) && count($itemData['specific_discounts']) > 0)
            {
                $item->itemSpecificDiscounts()->createMany($itemData['specific_discounts']);
            }

            $item->refresh();
            foreach ($item->storeIdsWithQty() as $id) {
                $itemHistoryData = [
                    "item_id" => $item->id,
                    "store_id" => $id,
                    "old_price" => $item->price, 
                    "old_original_price" => $item->original_price, 
                    "old_cost" => $item->cost, 
                    "reason_for_change" => "",
                    "action" => "add", 
                    "created_by" => $user->id,
                ];

                $item->itemHistory()->create($itemHistoryData);
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            throw $e;
        }

        return $item;
    }

    private function getSku($orgId)
    {
        $sku = strval(rand(1000000000, 9999999999));

        if (Item::where('organization_id', $orgId)->where('sku', $sku)->first()) return $this->getSku($orgId);

        return $sku;
    }
}

?>