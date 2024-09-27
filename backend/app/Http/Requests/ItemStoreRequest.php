<?php

namespace App\Http\Requests;

use App\CustomClass\RequestBuilder;
use App\Http\Requests\FormRequest;

class ItemStoreRequest extends FormRequest
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
            'title' => 'required|string',
            'description' => 'string|max:2000|nullable',
            'asin' => 'string|max:10|nullable',
            'mpn' => 'string|max:100|nullable',
            'merchant_name' => 'string|nullable',
            'merchant_price' => 'numeric|nullable',
            'price' => 'required|numeric|min:0',
            'cost' => 'numeric|min:0|nullable',
            'original_price' => 'required|numeric|min:0',
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
            'color' => 'string|max:100|nullable',
            'ean' => 'string|max:13|nullable',
            'elid' => 'string|max:12|nullable',
            'condition_description' => 'string|max:1000|nullable',
            'images' => 'array|max:5|nullable',
            'images.*' => 'string|distinct',
            'mongo_id' => 'nullable|string', // TEMP
            'sku' => 'string|min:10|max:10|nullable',
            'consignment_fee' => 'numeric|min:0|nullable'
        ]);
        $rules->foreignReference('created_by', 'users', false);
        $rules->foreignReference('classification_id', 'classifications', false);
        $rules->foreignReference('condition_id', 'conditions', false);
        $rules->foreignReference('consignor_id', 'consignors', false);
        $rules->upcRules();
        $rules->quantitiesRules();

        return $rules->arr;
    }

    public function messages()
    {
        $messages = new RequestBuilder();
        $messages->userIdMessages();
        $messages->userIdMessages('created_by');
        $messages->upcMessages();

        return $messages->arr;
    }
}
