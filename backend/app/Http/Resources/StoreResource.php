<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'preferences' => $this->whenLoaded('preferences'),
            'organization_id' => $this->organization_id,
            'organization' => $this->whenLoaded('organization'),
            'receipt_option_id' => $this->receipt_option_id,
            'receipt_option' => $this->whenLoaded('receiptOption'),
            'state_id' => $this->state_id,
            'state' => $this->whenLoaded('state'),
            'city' => $this->city,
            'address' => $this->address,
            'zip' => $this->zip,
            'name' => $this->name,
            'phone' => $this->phone,
            'tax_rate' => $this->tax_rate,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
            'posOrders' => $this->whenLoaded('posOrders'),
            'posReturns' => $this->whenLoaded('posReturns'),
        ];
    }
}
