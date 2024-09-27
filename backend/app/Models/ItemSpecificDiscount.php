<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemSpecificDiscount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = ['discount_description'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "item_id",
		"quantity",
		"discount_amount",
		"discount_type",
        "times_applicable",
        "can_stack",
        "active_at",
        "expires_at",
        "deleted_at",
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_stack' => 'boolean',
        'active_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    // RELATIONSHIPS
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

	// ATTRIBUTES
    public function getDiscountDescriptionAttribute()
    {
        $str = $this->discount_type == 'percent' ? $this->discount_amount * 100 . "% off" : '$' . $this->discount_amount / 100;
        $str = "Buy " . $this->quantity . " for " . $str;
        return $str;
    }
}
