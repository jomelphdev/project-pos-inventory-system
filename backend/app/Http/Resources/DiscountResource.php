<?php

namespace App\Http\Resources;

class DiscountResource extends BasePreferenceItemResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return array_merge(
            parent::toArray($request),
            [
                'pos_order_items' => PosOrderItem::collection($this->whenLoaded('posOrderItems')),
                'times_used' => $this->whenAppended('times_used'),
            ]
        );
    }
}
