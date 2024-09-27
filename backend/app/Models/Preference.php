<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Preference extends Model
{
    use HasFactory;
    
    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'owner', 
        'classifications', 
        'conditions', 
        'discounts',
        'checkoutStations',
        'stores',
        'consignors'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'owner_id',
      'organization_id',
      'using_merchant_partner',
      'merchant_username',
      'merchant_password',
      'merchant_id',
      'hide_pos_sales',
      'classifications_disabled',
      'conditions_disabled',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'owner_id' => 'integer',
        'organization_id' => 'integer',
        'using_merchant_partner' => 'boolean',
        'hide_pos_sales' => 'boolean',
        'classifications_disabled' => 'boolean',
        'conditions_disabled' => 'boolean',
    ];

    // RELATIONSHIPS

    public function classifications()
    {
        return $this->hasMany(\App\Models\Classification::class)->withTrashed()->orderBy('deleted_at', 'ASC')->orderBy('discount', 'DESC');
    }

    public function conditions()
    {
        return $this->hasMany(\App\Models\Condition::class)->withTrashed()->orderBy('deleted_at', 'ASC')->orderBy('discount', 'DESC');
    }

    public function discounts()
    {
        return $this->hasMany(\App\Models\Discount::class)->withTrashed()->orderBy('deleted_at', 'ASC')->orderBy('discount', 'DESC');
    }

    public function checkoutStations()
    {
        return $this->hasMany(\App\Models\CheckoutStation::class)->withTrashed()->orderBy('deleted_at', 'ASC');
    }

    public function stores()
    {
        return $this->hasMany(\App\Models\Store::class)->withTrashed()->orderBy('deleted_at', 'ASC');
    }

    public function storesVisible()
    {
        return $this->hasMany(\App\Models\Store::class, 'preference_id', 'id')->orderBy('deleted_at', 'ASC');
    }

    public function consignors()
    {
        return $this->hasMany(\App\Models\Consignor::class)->withTrashed()->orderBy('deleted_at', 'ASC');
    }

    public function receiptOptions()
    {
        return $this->hasMany(\App\Models\ReceiptOption::class);
    }

    public function employees()
    {
        return $this->hasManyThrough(\App\Models\User::class, \App\Models\Organization::class, 'id', 'organization_id', 'organization_id')->role(['employee', 'manager'])->withTrashed()->orderBy('deleted_at', 'ASC');
    }

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class)->without('preferences');
    }

    // ATTRIBUTES

    public function getEmployeesWithPermissionsAttribute()
    {
        return $this->employees()->get()->append(['user_role', 'user_permissions']);
    }

    public function getStoreIdsAttribute()
    {
        return $this->stores()->get()->pluck('id');
    }
}