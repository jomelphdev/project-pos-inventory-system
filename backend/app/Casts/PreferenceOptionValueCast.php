<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PreferenceOptionValueCast implements CastsAttributes
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
    public function get($model, $key, $value, $attributes)
    {
        $floatVal = floatval($value);

        if ($floatVal && intval($value) != $floatVal)
        {
            return (double) $value;
        }
        else if ($value == "true" || $value == "false")
        {
            return $value == "true" ? true : false;
        }
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
    public function set($model, $key, $value, $attributes)
    {
        if (is_float($value) || is_double($value))
        {
            return (string) $value;
        }
        else if (is_int($value))
        {
            return (string) ($value / 100);
        }
        else if (is_bool($value))
        {
            return $value ? 'true' : 'false';
        }
    }
}
