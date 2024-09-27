<?php

namespace App\Http\Resources;

class ConditionResource extends BasePreferenceItemResource
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
                'items' => Item::collection($this->whenLoaded('items')),
                'times_used' => $this->whenAppended('times_used'),
            ]
        );
    }
}
