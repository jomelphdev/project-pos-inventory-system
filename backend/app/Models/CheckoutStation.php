<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckoutStation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'preference_id',
        'store_id',
        'name',
        'terminal',
        'drawer_balance',
        'last_balanced'
    ];

    protected $casts = [
        'id' => 'integer',
        'preference_id' => 'integer',
        'store_id' => 'integer',
    ];

    // RELATIONSHIPS

    public function preferences()
    {
        return $this->belongsTo(\App\Models\Preference::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
