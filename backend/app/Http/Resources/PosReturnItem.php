<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PosReturnItem extends JsonResource
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

        return [
            'id' => $this->id,
            'pos_return_id' => $this->pos_return_id,
            'pos_return' => PosReturn::make($this->whenLoaded('posReturn')),
            'pos_order_item_id' => $this->pos_order_item_id,
            'pos_order_item' => $this->whenLoaded('posOrderItem'),
            'item_id' => $this->item_id,
            'item' => $item,
            'quantity_returned' => $this->quantity_returned,
            'action' => $this->action,
            'created_at' => $this->created_at,
            'cost' => $this->whenAppended('cost'),

            // Item data
            'title' => isset($item) ? $item->title : null,
            'classification_id' => isset($item) ? $item->classification_id : null,
            'sku' => isset($item) ? $item->sku : null,
            'images' => (isset($item) && isset($item['images'])) ? $item->images : null
        ];
    }
}
