<?php

namespace App\CustomClass;

trait PreferenceComponent
{
    /**
     * @return Float The discount percentage as a floating point number.
     */
    public function discountPercentage() 
    {
        $discount = is_null($this->discount) ? 0 : $this->discount / 100;
        
        return $discount;
    }
}