<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PosReturn extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'created_by' => $this->created_by,
            'created_by_user' => new CreatedBy($this->createdBy),
            'checkout_station_id' => $this->checkout_station_id,
            'checkout_station' => new CheckoutStationResource($this->whenLoaded('checkoutStation')),
            'organization_id' => $this->organization_id,
            'organization' => $this->whenLoaded('organization'),
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store'),
            'pos_order_id' => $this->pos_order_id,
            'pos_order' => $this->whenLoaded('posOrder'),
            'cash' => $this->cash,
            'card' => $this->card,
            'ebt' => $this->ebt,
            'gc' => $this->gc,
            'sub_total' => $this->sub_total,
            'tax' => $this->tax,
            'total' => $this->total,
            'created_at' => $this->created_at,
            'pos_return_items' => PosReturnItem::collection($this->whenLoaded('posReturnItems')),
            'quantity_returned' => $this->whenAppended('quantity_returned'),
            'return_cost' => $this->whenAppended('return_cost'),
        ];
    }
}
