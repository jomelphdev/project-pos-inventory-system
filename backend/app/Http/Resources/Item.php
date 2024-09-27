<?php

namespace App\Http\Resources;

class Item extends ItemBaseResource
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
                'condition_id' => $this->condition_id,
                'condition' => $this->whenLoaded('condition'),
                'consignor_id' => $this->consignor_id,
                'consignor' => $this->whenLoaded('consignor'),
                'manifest_item_id' => $this->manifest_item_id,
                'description' => $this->description,
                'cost' => (int) $this->cost,
                'sku' => $this->sku,
                'upc' => $this->upc,
                'asin' => $this->asin,
                'mpn' => $this->mpn,
                'merchant_name' => $this->merchant_name,
                'merchant_price' => $this->merchant_price,
                'length' => $this->length,
                'width' => $this->width,
                'depth' => $this->depth,
                'weight' => $this->weight,
                'brand' => $this->brand,
                'color' => $this->color,
                'ean' => $this->ean,
                'elid' => $this->elid,
                'condition_description' => $this->condition_description,
                'manifest_quantity_expected' => $this->manifest_quantity_expected,
                'consignment_fee' => $this->consignment_fee,
                'item_images' => $this->whenLoaded('itemImages'),
                'pos_order_items' => $this->whenLoaded('posOrderItems'),
                'pos_return_items' => $this->whenLoaded('posReturnItems'),
                'specific_discounts' => ItemSpecificDiscountResource::collection($this->whenLoaded('itemSpecificDiscounts')),
                'store_quantities' => $this->whenAppended('store_quantities'),
                'quantity_log' => $this->whenAppended('quantity_log'),
                'images' => $this->images,
            ]
        );
    }
}
