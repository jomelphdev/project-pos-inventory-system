<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use PhpUnitConversion\Unit\Mass;
use PhpUnitConversion\Unit\Mass\MilliGram;
use PhpUnitConversion\Unit\Mass\NanoGram;
use PhpUnitConversion\Unit\Mass\Ounce;

class MassCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return $value 
            ? 
            [
                'value' => round(Mass::from($value . 'mg')->to(Ounce::class)->getValue(), 2),
                'unit' => 'oz'
            ]
            :
            [
                'value' => null,
                'unit' => 'oz'
            ];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) return null;
        
        $unit = $value['unit'];
        $value = $value['value'];
        
        return round(Mass::from($value . $unit)->to(MilliGram::class)->getValue());
    }
}
