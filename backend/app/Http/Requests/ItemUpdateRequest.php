<?php

namespace App\Http\Requests;

use App\CustomClass\RequestBuilder;
use App\Http\Requests\FormRequest;

class ItemUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = new RequestBuilder([
            'title' => 'string|nullable',
            'description' => 'string|max:2000|nullable',
            'original_price' => 'numeric|min:0|nullable',
            'price' => 'numeric|min:0|nullable',
            'cost' => 'numeric|min:0|nullable',
            'merchant_name' => 'string|nullable',
            'merchant_price' => 'numeric|nullable',
            'length' => 'array|nullable',
            'length.value' => 'regex:/(\d+(?:\.\d+)?)/|nullable',
            'length.unit' => 'string|nullable',
            'width' => 'array|nullable',
            'width.value' => 'regex:/(\d+(?:\.\d+)?)/|nullable',
            'width.unit' => 'string|nullable',
            'depth' => 'array|nullable',
            'depth.value' => 'regex:/(\d+(?:\.\d+)?)/|nullable',
            'depth.unit' => 'string|nullable',
            'weight' => 'array|nullable',
            'weight.value' => 'regex:/(\d+(?:\.\d+)?)/|nullable',
            'weight.unit' => 'string|nullable',
            'brand' => 'string|max:75|nullable',
            'color' => 'string|max:20|nullable',
            'ean' => 'string|max:13|nullable',
            'elid' => 'string|max:12|nullable',
            'condition_description' => 'string|max:1000|nullable',
            'consignment_fee' => 'numeric|min:0|nullable',
            'images' => 'array|max:5|nullable',
            'images.*.image_url' => 'string|max:2000',
            'deleted_images' => 'array|nullable',
            'quantities' => 'array|nullable',
            'quantities.*.quantity_received' => 'integer',
            'specific_discounts' => 'array|nullable',
            'specific_discounts.*.quantity' => 'integer|min:1',
            'specific_discounts.*.discount_amount' => 'numeric',
            'specific_discounts.*.discount_type' => 'string',
            'specific_discounts.*.times_applicable' => 'integer|nullable'
        ]);
        $rules->foreignReference('classification_id', 'classifications', false);
        $rules->foreignReference('condition_id', 'conditions', false);
        $rules->foreignReference('quantities.*.id', 'quantities', false);
        $rules->foreignReference('images.*.id', 'item_images', false);

        return $rules->arr;
    }
}
