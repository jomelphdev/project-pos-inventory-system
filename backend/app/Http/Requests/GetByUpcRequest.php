<?php

namespace App\Http\Requests;

use App\CustomClass\RequestBuilder;
use App\Http\Requests\FormRequest;

class GetByUpcRequest extends FormRequest
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
        $rules->upcRules();

        return $rules->arr;
    }

    public function messages()
    {
        $messages = new RequestBuilder();
        $messages->userIdMessages();
        $messages->upcMessages();

        return $messages->arr;
    }
}
