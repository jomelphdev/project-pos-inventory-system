<?php

namespace App\Models;

use App\Casts\PhoneNumberCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['state', 'receiptOption'];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'preference_id',
        'organization_id',
        'receipt_option_id',
        'state_id',
        'city',
        'address',
        'zip',
        'name',
        'phone',
        'tax_rate',
        'deleted_at',
        'mongo_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'preference_id' => 'integer',
        'organization_id' => 'integer',
        'receipt_option_id' => 'integer',
        'state_id' => 'integer',
        'tax_rate' => 'decimal:5',
        'phone' => PhoneNumberCast::class,
    ];

    public function quantities()
    {
        return $this->hasMany(\App\Models\Quantity::class);
    }

    public function currentQuantities()
    {
        return $this->hasMany(\App\Models\CurrentQuantity::class);
    }

    public function posOrders()
    {
        return $this->hasMany(\App\Models\PosOrder::class);
    }

    public function posReturns()
    {
        return $this->hasMany(\App\Models\PosReturn::class);
    }

    public function onlineOrderItems()
    {
        return $this->hasMany(\App\Models\OnlineOrderItem::class);
    }

    public function checkoutStations()
    {
        return $this->hasMany(\App\Models\CheckoutStation::class);
    }

    public function receiptOption()
    {
        return $this->belongsTo(\App\Models\ReceiptOption::class);
    }

    public function state()
    {
        return $this->belongsTo(\App\Models\State::class);
    }

    public function preferences()
    {
        return $this->belongsTo(\App\Models\Preference::class, 'preference_id');
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
}
