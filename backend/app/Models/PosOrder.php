<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['posOrderItems'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'created_by',
        'checkout_station_id',
        'organization_id',
        'store_id',
        'gift_card_id',
        'cash',
        'card',
        'ebt',
        'gc',
        'sub_total',
        'tax',
        'total',
        'amount_paid',
        'change',
        'tax_rate',
        'processor_reference'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'created_by' => 'integer',
        'checkout_station_id' => 'integer',
        'organization_id' => 'integer',
        'store_id' => 'integer',
        'gift_card_id' => 'integer',
        'cash' => MoneyCast::class,
        'card' => MoneyCast::class,
        'ebt' => MoneyCast::class,
        'gc' => MoneyCast::class,
        'sub_total' => MoneyCast::class,
        'tax' => MoneyCast::class,
        'total' => MoneyCast::class,
        'amount_paid' => MoneyCast::class,
        'change' => MoneyCast::class,
        'tax_rate' => 'decimal:4',
    ];

    // RELATIONSHIPS

    public function posOrderItems()
    {
        return $this->hasMany(\App\Models\PosOrderItem::class);
    }

    public function posReturns()
    {
        return $this->hasMany(\App\Models\PosReturn::class);
    }

    public function posReturnItems()
    {
        return $this->hasManyThrough(\App\Models\PosReturnItem::class, \App\Models\PosReturn::class, 'pos_order_id', 'pos_return_id', 'id', 'id');
    }

    public function items()
    {
        return $this->hasManyThrough(\App\Models\Item::class, \App\Models\PosOrderItem::class, 'pos_order_id', 'id', 'id', 'item_id');
    }

    public function preferences()
    {
        return $this->hasOneThrough(\App\Models\Preference::class, \App\Models\Organization::class, 'id', 'organization_id', 'organization_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function checkoutStation()
    {
        return $this->belongsTo(\App\Models\CheckoutStation::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    // ATTRIBUTES
    public function getOrderCostAttribute()
    {
        return $this->items()->sum('cost');
    }

    public function getOrderWideDiscountAttribute()
    {
        $orderItemsWithDiscount = $this->posOrderItems()->whereNotNull('discount_id')->get();
        $toalItemsOnOrder = $this->select('id')->withCount('posOrderItems');
        $discounts = array_unique($orderItemsWithDiscount->pluck('discount_id'));

        if ($toalItemsOnOrder->pos_order_items_count == count($orderItemsWithDiscount) && count($discounts) == 1)
        {
            return Discount::find($discounts[0]);
        }

        return false;
    }

    public function getDiscountsUsedOnOrderAttribute()
    {
        return $this->posOrderItems()->select('discount_id')->orderBy('discount_id')->get()->pluck('discount_id')->toArray();
    }

    public function getTotalDiscountAmountAttribute()
    {
        return $this->posOrderItems->sum('discount_amount');
    }

    public function getDiscountAmountPercentAttribute()
    {
        return $this->total_discount_amount / ($this->total_discount_amount + $this->sub_total);
    }

    public function getMerchantIdAttribute()
    {
        return $this->preferences->merchant_id;
    }

    // QUERY SCOPES 
    
    public function scopeReportForStore(Builder $query, $storeId, Carbon $startDate, Carbon $endDate=null)
    {
        $dateRange = getDateRangeForReports($storeId, $startDate, $endDate);
        $reportSelect = [
            'id', 'created_by', 'store_id',
            'cash', 'card', 'ebt', 
            'sub_total', 'tax', 'total', 
            'created_at', 'change', 'checkout_station_id',
        ];

        return $query->select($reportSelect)
            ->without('posOrderItems')
            ->withQuantityOrdered()
            ->where('store_id', $storeId)
            ->whereBetween('created_at', array_values($dateRange));
    }

    public function scopeWithQuantityOrdered(Builder $query)
    {
        return $query->withSum('posOrderItems as quantity_ordered', 'quantity_ordered');
    }
}
