<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsignorResource extends JsonResource
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
            'name' => $this->name,
            'consignment_fee_percentage' => $this->consignment_fee_percentage,
            'deleted_at' => $this->deleted_at
        ];
    }
}
