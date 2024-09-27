<?php

namespace App\Http\Requests;

use App\CustomClass\RequestBuilder;
use App\Http\Requests\FormRequest;

class QueryItemsRequest extends FormRequest
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
        $rules = new RequestBuilder();
        $rules->foreignReference('user_id', 'users');

        return $rules->arr;
    }

    public function messages()
    {
        $messages = new RequestBuilder();
        $messages->userIdMessages();

        return $messages->arr;
    }
}
