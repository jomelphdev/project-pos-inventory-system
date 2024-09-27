<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ManifestItemResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'organization' => $this->whenLoaded('organization'),
            'manifest_id' => $this->manifest_id,
            'manifest' => $this->whenLoaded('manifest'),
            'title' => $this->title,
            'description' => $this->description,
            'price' => (int) $this->price,
            'quantity' => $this->quantity,
            'upc' => $this->upc,
            'asin' => $this->asin,
            'mpn' => $this->mpn,
            'cost' => (int) $this->cost,
            'fn_sku' => $this->fn_sku,
            'lpn' => $this->lpn,
            'images' => $this->images,
            'quantity_expected' => $this->quantity_expected,
        ];
    }
}
