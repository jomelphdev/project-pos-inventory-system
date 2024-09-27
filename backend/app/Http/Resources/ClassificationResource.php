<?php

namespace App\Http\Resources;

class ClassificationResource extends BasePreferenceItemResource
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
                "ebt_stores" => $this->whenAppended("ebt_stores"),
                "non_taxed_stores" => $this->whenAppended("non_taxed_stores"),
                'items' => Item::collection($this->whenLoaded('items')),
                'times_used' => $this->whenAppended('times_used'),
            ]
        );
    }
}
