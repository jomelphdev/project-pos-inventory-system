<?php

namespace App\Http\Requests;

use App\CustomClass\RequestBuilder;
use App\Http\Requests\FormRequest;

class PosOrderStoreRequest extends FormRequest
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
            'items' => 'required|array|min:1',
            'tax_rate' => 'required|numeric',
            'terminal_hsn' => 'nullable|string',
            'is_debit' => 'nullable|boolean'
        ]);
        $rules->foreignReference('created_by', 'users');
        $rules->foreignReference('checkout_station_id', 'checkout_stations', false);
        $rules->foreignReference('organization_id', 'organizations');
        $rules->foreignReference('store_id', 'stores');
        $rules->unsignedNumbericRules('cash');
        $rules->unsignedNumbericRules('card');
        $rules->unsignedNumbericRules('ebt');
        $rules->unsignedNumbericRules('gc');
        $rules->unsignedNumbericRules('sub_total');
        $rules->unsignedNumbericRules('tax');
        $rules->unsignedNumbericRules('total');
        $rules->unsignedNumbericRules('amount_paid');
        $rules->unsignedNumbericRules('change');

        return $rules->arr;
    }

    public function messages()
    {
        $messages = new RequestBuilder([
            'amount_paid.gte' => 'Amount paid is less than the order total.'
        ]);

        return $messages->arr;
    }
}
