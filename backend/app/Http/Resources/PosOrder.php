<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PosOrder extends JsonResource
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
            'checkout_station_id' => $this->checkout_station_id,
            'checkout_station' => new CheckoutStationResource($this->whenLoaded('checkoutStation')),
            'created_by_user' => new CreatedBy($this->whenLoaded('createdBy')),
            'organization_id' => $this->organization_id,
            'organization' => $this->whenLoaded('organization'),
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store'),
            'cash' => $this->cash,
            'card' => $this->card,
            'ebt' => $this->ebt,
            'gc' => $this->gc,
            'sub_total' => $this->sub_total,
            'tax' => $this->tax,
            'total' => $this->total,
            'amount_paid' => $this->amount_paid,
            'change' => $this->change,
            'tax_rate' => $this->tax_rate,
            'processor_reference' => $this->processor_reference,
            'created_at' => $this->created_at,
            'pos_order_items' => PosOrderItem::collection($this->whenLoaded('posOrderItems')),
            'pos_return_items' => PosReturnItem::collection($this->whenLoaded('posReturnItems')),
            'pos_returns' =>  PosReturn::collection($this->whenLoaded('posReturns')),
            'items' => Item::collection($this->whenLoaded('items')),
            'quantity_ordered' => $this->when(isset($this->quantity_ordered), (int) $this->quantity_ordered),
            'order_cost' => $this->whenAppended('order_cost'),
            'order_wide_discount' => $this->whenAppended('order_wide_discount'),
            'discounts_used_on_order' => $this->whenAppended('discounts_used_on_order'),
            'cash_left' => $this->when(isset($this->cash_left), (int) $this->cash_left),
            'card_left' => $this->when(isset($this->card_left), (int) $this->card_left),
            'ebt_left' => $this->when(isset($this->ebt_left), (int) $this->ebt_left),
            'processing_details' => $this->when(isset($this->processing_details), $this->processing_details),
            'session_key' => $this->when(isset($this->session_key), $this->session_key),
        ];
    }
}
