<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PosOrderUpdateRequest extends FormRequest
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
        return [
            'cash' => 'integer',
            'card' => 'integer',
            'ebt' => 'integer',
            'sub_total' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'change' => 'integer',
            'tax_rate' => 'integer',
            'items' => 'array|min:1',
            'items.*.item_id' => 'required_with:items|integer|exists:items,id'
        ];
    }
}
