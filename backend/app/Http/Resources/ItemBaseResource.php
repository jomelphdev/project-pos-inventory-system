<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemBaseResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'organization' => $this->whenLoaded('organization'),
            'classification_id' => $this->classification_id,
            'classification' => $this->whenLoaded('classification'),
            'title' => $this->title,
            'price' => (int) $this->price,
            'original_price' => (int) $this->original_price,
            'created_at' => $this->created_at,
        ];
    }
}
