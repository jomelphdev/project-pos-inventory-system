<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentQuantity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'store_id',
        'quantity',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'item_id' => 'integer',
        'store_id' => 'integer',
    ];

    // RELATIONSHIPS

    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
