<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddedItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'created_by',
        'organization_id',
        'classification_id',
        'title',
        'price',
        'original_price',
        'mongo_id',
        'created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'created_by' => 'integer',
        'organization_id' => 'integer',
        'classification_id' => 'integer',
        'price' => MoneyCast::class,
        'original_price' => MoneyCast::class,
    ];

    // RELATIONSHIPS

    public function posOrderItem()
    {
        return $this->hasOne(\App\Models\PosOrderItem::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function classification()
    {
        return $this->belongsTo(\App\Models\Classification::class)->withTrashed();
    }
}
