<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReturn extends Model
{
    use HasFactory;

    protected $appends = ['quantity_returned'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['posReturnItems'];
    
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
        'pos_order_id',
        'cash',
        'card',
        'ebt',
        'gc',
        'sub_total',
        'tax',
        'total',
        'tax_rate',
        'mongo_id',
        'created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'created_by' => 'integer',
        'organization_id' => 'integer',
        'store_id' => 'integer',
        'pos_order_id' => 'integer',
        'cash' => MoneyCast::class,
        'card' => MoneyCast::class,
        'ebt' => MoneyCast::class,
        'gc' => MoneyCast::class,
        'sub_total' => MoneyCast::class,
        'tax' => MoneyCast::class,
        'total' => MoneyCast::class,
        'tax_rate' => 'decimal:4',
    ];

    public function posReturnItems()
    {
        return $this->hasMany(\App\Models\PosReturnItem::class);
    }

    public function posOrderItems()
    {
        return $this->hasManyThrough(\App\Models\PosOrderItem::class,\App\Models\PosReturnItem::class, 'pos_return_id', 'id', 'id', 'pos_order_item_id');
    }

    public function items()
    {
        return $this->hasManyThrough(\App\Models\Item::class, \App\Models\PosReturnItem::class, 'pos_return_id', 'id', 'id', 'item_id');
    }

    public function posOrder()
    {
        return $this->belongsTo(\App\Models\PosOrder::class);
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

    public function getQuantityReturnedAttribute()
    {
        return $this->posReturnItems()->sum('quantity_returned');
    }

    public function getReturnCostAttribute()
    {
        return $this->items()->sum('cost');
    }

    /* QUERY SCOPES */
    
    public function scopeReportForStore(Builder $query, $storeId, Carbon $startDate, Carbon $endDate=null)
    {
        $dateRange = getDateRangeForReports($storeId, $startDate, $endDate);
        $reportSelect = [
            'id', 'created_by', 'store_id', 
            'pos_order_id', 'cash', 'card', 
            'ebt', 'sub_total', 'tax', 
            'total', 'created_at', 'checkout_station_id'
        ];

        return $query->with('posReturnItems.item')
            ->where('store_id', $storeId)
            ->whereBetween('created_at', array_values($dateRange))
            ->select($reportSelect);
    }
}
