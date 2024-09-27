<?php

namespace App\Models;

use App\Casts\PreferenceOptionValueCast;
use App\Casts\PreferenceOptionValueTypeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferenceOption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'store_id',
        'model_id',
        'model_type',
        'key',
        'value',
        'value_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'model_id' => 'integer',
        'value' => PreferenceOptionValueCast::class,
    ];

    // RELATIONSHIPS

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function model()
    {
        return $this->morphTo();
    }
}
