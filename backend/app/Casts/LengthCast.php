<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use PhpUnitConversion\Unit\Length;
use PhpUnitConversion\Unit\Length\Inch;
use PhpUnitConversion\Unit\Length\MicroMeter;

class LengthCast implements CastsAttributes
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
        return $value ? 
            [
                'value' => round(Length::from($value . 'μm')->to(Inch::class)->getValue(), 2),
                'unit' => 'in'
            ] 
            :
            [
                'value' => null,
                'unit' => 'in'
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
        
        return round(Length::from($value . $unit)->to(MicroMeter::class)->getValue());
    }
}
