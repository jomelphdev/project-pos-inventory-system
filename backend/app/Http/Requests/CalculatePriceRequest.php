<?php

namespace App\Http\Requests;

use App\CustomClass\RequestBuilder;
use Illuminate\Foundation\Http\FormRequest;

class CalculatePriceRequest extends FormRequest
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
            'price' => 'required|integer',
            'discount_amount' => 'nullable|integer'
        ]);
        $rules->foreignReference('classification_id', 'classifications', false);
        $rules->foreignReference('condition_id', 'conditions', false);
        $rules->foreignReference('discount_id', 'discounts', false);
        return $rules->arr;
    }
}
