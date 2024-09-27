<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

class Organization extends Model
{
    use HasFactory, Billable;

    protected $appends = ['subscription_required'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['preferences', 'subscription'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'trial_ends_at',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'quickbooks_realm_id',
        'quickbooks_refresh_token',
        'quickbooks_access_token',
        'is_quickbooks_authenticated',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'trial_ends_at' => 'datetime'
    ];

    // RELATIONSHIPS

    public function preferences()
    {
        return $this->hasOne(\App\Models\Preference::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function employees()
    {
        return $this->hasMany(\App\Models\User::class)->role(['employee', 'manager']);
    }

    public function userFeedback()
    {
        return $this->hasMany(\App\Models\UserFeedback::class);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\Item::class);
    }

    public function quickBooksJournalEntries()
    {
        return $this->hasMany(\App\Models\QuickBooksJournalEntry::class);
    }

    // ATTRIBUTES

    public function getSubscriptionRequiredAttribute()
    {
        return !($this->subscription && $this->subscription->active()) && !$this->onGenericTrial();
    }

    public function getUpdatedPaymentMethodRequiredAttribute()
    {
        return $this->subscription->hasIncompletePayment();
    }
}
