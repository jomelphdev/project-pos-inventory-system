<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, HasFactory, HasRoles, SoftDeletes;

    protected $guard_name = 'web';
    
    protected $appends = ['full_name'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'first_name', 
        'last_name', 
        'email', 
        'username', 
        'password',
        'deleted_at',
        'mongo_id',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'organization_id' => 'integer',
        'email_verified_at' => 'datetime',
    ];

    // RELATIONSHIPS

    public function preferences() {
        return $this->hasOneThrough(\App\Models\Preference::class, \App\Models\Organization::class, 'id', 'organization_id', 'organization_id');
    }

    public function stores()
    {
        return $this->hasMany(\App\Models\Store::class);
    }

    public function userFeedback()
    {
        return $this->hasMany(\App\Models\UserFeedback::class);
    }

    public function notifications()
    {
        return $this->hasMany(\App\Models\Notifications::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(\App\Models\Notification::class)->where('read', false);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    // ATTRIBUTES

    public function getUserPermissionsAttribute() 
    {
        $permissions = $this->getAllPermissions()->pluck('name')->toArray();
        unset($this->permissions);
        return $permissions;
    }

    public function getUserRoleAttribute() 
    {
        $role = $this->roles()->without('permissions')->select('name', 'id')->first();
        unset($this->roles);
        return $role;
    }

    public function getFullNameAttribute() 
    {
        if ($this->last_name) return $this->first_name . ' ' . $this->last_name;
        return $this->first_name;
    }

    public function getSubscriptionRequiredAttribute()
    {
        return $this->organization->subscription_required;
    }

    // QUERY SCOPES

    public function scopeWithAllPreferences(Builder $query, $username)
    {
        return $query->with([
                'organization.preferences.stores',
                'organization.preferences.classifications',
                'organization.preferences.conditions',
                'organization.preferences.discounts'
            ])
            ->where('username', $username);
    }
}
