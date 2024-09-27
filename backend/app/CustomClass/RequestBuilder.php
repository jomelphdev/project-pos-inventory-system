<?php

namespace App\CustomClass;

class RequestBuilder
{
    // TODO: figure out how to use variables in messages.
    public $arr;

    function __construct(array $arr=[])
    {
        $this->arr = $arr;   
    }

    public function foreignReference($field, $existsIn, $required=true)
    {
        if ($required)
        {
            $this->arr[$field] = 'integer|exists:' . $existsIn . ',id';
    
        }
        else
        {
            $this->arr[$field] = 'nullable|integer|exists:' . $existsIn . ',id';
        }
    }
    public function userIdMessages($userField='user_id')
    {
        $messages = [
            $userField . '.required' => 'User ID is required to make this request.', 
            $userField . '.integer' => 'User ID is supposed to be an integer.',
            $userField . '.exists' => 'This User ID does not exist please enter a valid User ID and try again.'
        ];
        $this->arr = array_merge($this->arr, $messages);
        
        return $this->arr;
    }

    public function skuRules()
    {
        $this->arr['sku'] = 'required|string|min:10|max:10|regex:/^[0-9]+$/';
        return $this->arr;
    }

    public function skuMessages()
    {
        $messages = [
            'sku.required' => 'Item SKU is required to make this request.',
            'sku.string' => 'SKU is expected to be a string.',
            'sku.min' => 'SKU must be 10 characters.',
            'sku.max' => 'SKU must be 10 characters.',
            'sku.regex' => 'SKU must be a string that consists only of integers [0-9].'
        ];
        $this->arr = array_merge($this->arr, $messages);

        return $this->arr;
    }

    public function upcRules()
    {
        $this->arr['upc'] = 'string|min:12|max:13|regex:/^[0-9]+$/|nullable';
        return $this->arr;
    }
    
    public function upcMessages()
    {
        $messages = [
            'upc.required' => 'Item UPC is required to make this request.',
            'upc.string' => 'UPC is expected to be a string.',
            'upc.min' => 'UPC must be 12 characters.',
            'upc.max' => 'UPC must be 12 characters.',
            'upc.regex' => 'UPC must be a string that consists only of integers [0-9].'
        ];
        $this->arr = array_merge($this->arr, $messages);

        return $this->arr;
    }

    public function unsignedNumbericRules($key)
    {
        $this->arr[$key] = 'required|numeric|min:0';
        return $this->arr;
    }

    public function quantitiesRules()
    {
        $prefix = 'quantities.*.';
        $this->arr['quantities'] = 'required|array|min:1';
        $this->arr[$prefix . 'quantity_received'] = 'required|integer|min:0';

        $this->foreignReference($prefix . 'store_id', 'stores');
        $this->foreignReference($prefix . 'user_id', 'users');
        $this->arr[$prefix . 'message'] = 'required|string';
        // Not referenced as $this->foreignReference() b/c it is not a required reference.
        $this->arr[$prefix . 'manifest_number'] = 'integer|exists:manifests,id';
    }
}