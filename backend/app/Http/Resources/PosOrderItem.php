<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PosOrderItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $item = $this->whenLoaded('item', Item::make($this->item));
        $addedItem = $this->whenLoaded('addedItem', ItemBaseResource::make($this->addedItem));
        $useItem = $addedItem ? $addedItem : $item;

        return [
            'id' => $this->id,
            'pos_order_id' => $this->pos_order_id,
            'pos_order' => PosOrder::make($this->whenLoaded('posOrder')),
            'item_id' => $this->item_id,
            'item' => $item,
            'added_item_id' => $this->added_item_id,
            'added_item' => $addedItem,
            'item_specific_discount_id' => $this->item_specific_discount_id,
            'item_specific_discount_quantity' => $this->item_specific_discount_quantity,
            'item_specific_discount_times_applied' => $this->item_specific_discount_times_applied,
            'item_specific_discount_can_stack' => $this->item_specific_discount_can_stack,
            'item_specific_discount_original_amount' => $this->item_specific_discount_original_amount,
            'item_specific_discount_amount' => $this->item_specific_discount_amount,
            'item_specific_discount_type' => $this->item_specific_discount_type,
            'discount_description' => $this->when(isset($this->item_specific_discount_id), $this->discount_description),
            'discount_id' => $this->discount_id,
            'discount_amount' => $this->discount_amount,
            'discount_amount_type' => $this->discount_amount_type,
            'discount' => $this->whenLoaded('discount'),
            'price' => $this->price,
            'original_price' => isset($this->discount_amount) 
                ? $this->price + $this->discount_amount
                : (isset($this->discount_percent)
                    ? intval($this->price / (1 - $this->discount_percent))
                    : $this->price),
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_returned' => $this->whenAppended('quantity_returned'),
            'quantity_left_to_return' => $this->whenAppended('quantity_left_to_return'),
            'total' => $this->whenAppended('total', (int) $this->total),
            'cost' => $this->whenAppended('cost', (int) $this->cost),
            'is_ebt' => $this->is_ebt,
            'is_taxed' => $this->is_taxed,
            'consignment_fee' => $this->consignment_fee,

            // Item data
            'title' => $useItem ? $useItem['title'] : null,
            'classification_id' => $useItem ? $useItem['classification_id'] : null,
            'sku' => ($useItem && isset($useItem['sku'])) ? $useItem['sku'] : null,
            'images' => ($useItem && isset($useItem['images'])) ? $useItem['images'] : null
        ];
    }
}
