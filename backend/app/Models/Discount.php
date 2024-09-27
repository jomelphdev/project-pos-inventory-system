<?php

namespace App\Models;

use App\Casts\PercentageCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use \App\CustomClass\PreferenceComponent;
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'preference_id',
        'name',
        'discount',
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
        'discount' => PercentageCast::class,
    ];

    // RELATIONSHIPS

    public function preferences()
    {
        return $this->belongsTo(\App\Models\Preference::class);
    }

    public function posOrderItems()
    {
        return $this->hasMany(\App\Models\PosOrderItem::class);
    }

    public function preferenceOptions()
    {
        return $this->morphMany(\App\Models\PreferenceOption::class, 'model');
    }

    // ATTRIBUTES

    public function getTimesUsedAttribute()
    {
        return $this->posOrderItems()->distinct('pos_order_id')->count();
    }

    // OBSERVERS

    public static function boot()
    {
        parent::boot();

        static::deleting(function (Discount $discount) {
            if ($discount->isForceDeleting())
            {
                $discount->preferenceOptions()->forceDelete();
            }
        });
    }
}
