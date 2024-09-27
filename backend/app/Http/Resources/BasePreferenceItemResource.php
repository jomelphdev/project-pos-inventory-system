<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BasePreferenceItemResource extends JsonResource
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
            'discount' => $this->discount,
            'preference_options' => $this->whenLoaded('preferenceOptions'),
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
