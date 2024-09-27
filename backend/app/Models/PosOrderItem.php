<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Services\QuantityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;
use Money\Money;

class PosOrderItem extends Model
{
    use HasFactory;

    protected $appends = ['total', 'quantity_left_to_return', 'cost'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['item', 'addedItem'];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pos_order_id',
        'item_id',
        'added_item_id',
        'item_specific_discount_id',
        'item_specific_discount_quantity',
        'item_specific_discount_times_applied',
        'item_specific_discount_can_stack',
        'item_specific_discount_original_amount',
        'item_specific_discount_amount',
        'item_specific_discount_type',
        'discount_id',
        'discount_percent',
        'price',
        'original_price',
        'quantity_ordered',
        'is_ebt',
        'is_taxed',
        'consignment_fee',
        'discount_amount',
        'discount_amount_type',
        'item_specific_discount_active_at',
        'item_specific_discount_expires_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'pos_order_id' => 'integer',
        'item_id' => 'integer',
        'added_item_id' => 'integer',
        'item_specific_discount_id' => 'integer',
        'item_specific_discount_times_applied' => 'integer',
        'discount_id' => 'integer',
        'discount_percent' => 'decimal:4',
        'price' => MoneyCast::class,
        'quantity_ordered' => 'integer',
        'is_ebt' => 'boolean',
        'is_taxed' => 'boolean',
        'consignment_fee' => 'integer',
    ];

    // RELATIONSHIPS

    public function posOrder()
    {
        return $this->belongsTo(\App\Models\PosOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class);
    }

    public function addedItem() 
    {
        return $this->belongsTo(\App\Models\AddedItem::class);
    }

    public function itemSpecificDiscount() 
    {
        return $this->belongsTo(\App\Models\ItemSpecificDiscount::class);
    }

    public function discount()
    {
        return $this->belongsTo(\App\Models\Discount::class);
    }

    public function posReturnItems()
    {
        return $this->hasMany(\App\Models\PosReturnItem::class);
    }

    // ATTRIBUTES

    public function getQuantityReturnedAttribute()
    {
        return $this->posReturnItems()->get()->pluck('quantity_returned')->sum();
    }

    public function getQuantityLeftToReturnAttribute()
    {
        return $this->quantity_ordered - $this->posReturnItems()->get()->pluck('quantity_returned')->sum();
    }

    public function getTotalAttribute() 
    {
        $total = Money::USD($this->price)->multiply($this->quantity_ordered);

        if (isset($this->item_specific_discount_id))
        {
            if ($this->item_specific_discount_type == 'amount')
            {
                $baseToDiscount = $this->price * $this->item_specific_discount_quantity * $this->item_specific_discount_times_applied;
                $discountAmount = Money::USD($baseToDiscount)->subtract(Money::USD($this->item_specific_discount_amount * $this->item_specific_discount_times_applied));
            } else 
            {
                $discountAmount = Money::USD($this->price)->multiply($this->item_specific_discount_amount)->multiply($this->item_specific_discount_quantity * $this->item_specific_discount_times_applied);
            }

            $total = $total->subtract($discountAmount);
        }

        return (int) $total->getAmount();
    }

    public function getDiscountDescriptionAttribute()
    {
        if (!$this->item_specific_discount_id) return null;

        $str = "Buy " . $this->item_specific_discount_quantity . " for ";

        if ($this->item_specific_discount_type == 'amount')
        {
            $str .= '$' . ($this->item_specific_discount_original_amount != $this->item_specific_discount_amount ? intval($this->item_specific_discount_amount) / 100 . ' (was $' . intval($this->item_specific_discount_original_amount) / 100 . ')' : intval($this->item_specific_discount_amount) / 100);
        } else 
        {
            $str .= $this->item_specific_discount_amount * 100 . '% off';
        }

        return $str;
    }

    public function getCostAttribute()
    {
        if (!$this->item) 
        {
            return null;
        }
        
        $cost = Money::USD($this->item->cost)->multiply($this->quantity_ordered);
        return (int) $cost->getAmount();
    }

    // QUERY SCOPES

    public function scopeExclude(Builder $query, $excludes=[])
    {
        $columns = Schema::getColumnListing($this->getTable());
        return $query->select(array_diff($columns, (array) $excludes));
    }

    // OBSERVERS

    public static function boot()
    {
        parent::boot();

        static::created(function (PosOrderItem $item) {
            if (!$item->item_id) return;

            $qs = new QuantityService;
            $qs->setCurrentQuantity($item->posOrder->store_id, $item->item_id);
        });
    }
}
