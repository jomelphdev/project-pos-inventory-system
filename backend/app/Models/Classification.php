<?php

namespace App\Models;

use App\Casts\PercentageCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Classification extends Model
{

    use \App\CustomClass\PreferenceComponent;
    use HasFactory, SoftDeletes;

    protected $appends = ['ebt_stores', 'non_taxed_stores'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ["preferenceOptions"];

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

    public function getEbtStoresAttribute()
    {
        return $this->preferenceOptions()
            ->where([
                ["key", "is_ebt"],
                ["value", "true"]
            ])
            ->get()
            ->pluck("store_id")
            ->unique();
    }

    public function getNonTaxedStoresAttribute()
    {
        return $this->preferenceOptions()
            ->where([
                ["key", "is_taxed"],
                ["value", "false"]
            ])
            ->get()
            ->pluck("store_id")
            ->unique();
    }

    // METHODS

    public function isEbt(int $storeId)
    {
        return in_array($storeId, $this->ebt_stores->toArray());
    }

    public function isTaxed(int $storeId)
    {
        return !in_array($storeId, $this->non_taxed_stores->toArray());
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

        static::deleting(function (Classification $classification) {
            if ($classification->isForceDeleting())
            {
                $classification->preferenceOptions()->forceDelete();
            }
        });
    }
}
