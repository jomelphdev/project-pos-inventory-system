<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemSpecificDiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
    return [
            "id" => $this->id,
			"item_id" => $this->item_id,
			"item" => new Item($this->whenLoaded("item")),
			"quantity" => $this->quantity,
			"discount_amount" => $this->discount_type == "amount" ? intval($this->discount_amount) : $this->discount_amount,
			"discount_type" => $this->discount_type,
            "discount_description" => $this->whenAppended("discount_description"),
            "times_applicable" => $this->times_applicable,
            "can_stack" => $this->can_stack,
            "active_at" => $this->active_at,
            "expires_at" => $this->expires_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}