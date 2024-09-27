<?php

namespace App\Models;

use App\Services\QuantityService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Money\Money;

class PosReturnItem extends Model
{
    use HasFactory;

    protected $appends = ['cost'];
    
    /**
     * The attributes that are mass assignable.
     * action : 1 == back to inventory 0 == discard
     *
     * @var array
     */
    protected $fillable = [
        'pos_return_id',
        'pos_order_item_id',
        'item_id',
        'quantity_returned',
        'action',
        'consignment_fee',
        'created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'pos_return_id' => 'integer',
        'pos_order_item_id' => 'integer',
        'item_id' => 'integer',
        'quantity_returned' => 'integer',
        'action' => 'integer',
        'consignment_fee' => 'integer',
    ];


    public function posReturn()
    {
        return $this->belongsTo(\App\Models\PosReturn::class);
    }

    public function posOrderItem()
    {
        return $this->belongsTo(\App\Models\PosOrderItem::class);
    }

    public function item() 
    {
        return $this->belongsTo(\App\Models\Item::class);
    }

    // ATTRIBUTES
    public function getCostAttribute()
    {
        if (!$this->item) 
        {
            return null;
        }

        $cost = Money::USD($this->item->cost)->multiply($this->quantity_returned);
        return (int) $cost->getAmount();
    }

    // OBSERVERS

    public static function boot()
    {
        parent::boot();

        static::created(function (PosReturnItem $item) {
            if (!$item->item_id) return;
            
            $qs = new QuantityService;
            $qs->setCurrentQuantity($item->posReturn->store_id, $item->item_id);
        });
    }
}
