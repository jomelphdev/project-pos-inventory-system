<?php

namespace App\Http\Requests;

use App\CustomClass\RequestBuilder;
use Illuminate\Foundation\Http\FormRequest;

class PosReturnStoreRequest extends FormRequest
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
        ]);
        $rules->foreignReference('created_by', 'users');
        $rules->foreignReference('checkout_station_id', 'checkout_stations', false);
        $rules->foreignReference('organization_id', 'organizations');
        $rules->foreignReference('store_id', 'stores');
        $rules->foreignReference('pos_order_id', 'pos_orders');
        $rules->unsignedNumbericRules('cash');
        $rules->unsignedNumbericRules('card');
        $rules->unsignedNumbericRules('ebt');
        $rules->unsignedNumbericRules('gc');
        $rules->unsignedNumbericRules('sub_total');
        $rules->unsignedNumbericRules('tax');
        $rules->unsignedNumbericRules('total');

        return $rules->arr;
    }
}
