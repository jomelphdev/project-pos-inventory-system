<?php

namespace App\Models;

use App\Casts\PercentageCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condition extends Model
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

    public function items()
    {
        return $this->hasMany(\App\Models\Item::class);
    }

    public function preferences()
    {
        return $this->belongsTo(\App\Models\Preference::class);
    }

    public function preferenceOptions()
    {
        return $this->morphMany(\App\Models\PreferenceOption::class, 'model');
    }

    // ATTRIBUTES

    public function getTimesUsedAttribute()
    {
        return $this->items()->count();
    }

    // OBSERVERS

    public static function boot()
    {
        parent::boot();

        static::deleting(function (Condition $condition) {
            if ($condition->isForceDeleting())
            {
                $condition->preferenceOptions()->forceDelete();
            }
        });
    }
}
