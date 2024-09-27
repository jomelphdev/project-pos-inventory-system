<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutStationResource extends JsonResource
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
            'preference_id' => $this->preference_id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'terminal' => $this->terminal,
            'drawer_balance' => $this->drawer_balance,
            'preferences' => $this->whenLoaded('preferences'),
            'store' => $this->whenLoaded('store'),
            'deleted_at' => $this->deleted_at
        ];
    }
}
