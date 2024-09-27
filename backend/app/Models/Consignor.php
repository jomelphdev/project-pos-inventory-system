<?php

namespace App\Models;

use App\Casts\PercentageCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consignor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'preference_id',
        'name',
        'consignment_fee_percentage'
    ];

    protected $casts = [
        'id' => 'integer',
        'preference_id' => 'integer',
        'consignment_fee_percentage' => PercentageCast::class
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
}
