<?php

namespace App\Models;

use App\Services\QuantityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quantity extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'store_id',
        'created_by',
        'quantity_received',
        'message',
        'manifest_number',
        'is_transfer',
        'created_at'
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
        'created_by' => 'integer',
        'quantity_received' => 'integer',
        'message' => 'string',
        'manifest_number' => 'integer',
        'is_tansfer' => 'boolean'
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

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // OBSERVERS

    public static function boot()
    {
        parent::boot();

        static::created(function (Quantity $qty) {
            $qs = new QuantityService;
            $qs->setCurrentQuantity($qty->store_id, $qty->item_id, $qty->quantity_received);
        });
    }
}
