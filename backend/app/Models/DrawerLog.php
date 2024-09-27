<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrawerLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'checkout_station_id',
        'started_at',
        'ended_at',
        'expected_difference',
        'actual_difference'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'organization_id' => 'integer',
        'checkout_station_id' => 'integer',
    ];

    // RELATIONSHIPS
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function checkoutStation()
    {
        return $this->belongsTo(CheckoutStation::class);
    }
}
