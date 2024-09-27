<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManifestItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'manifest_id',
        'title',
        'description',
        'price',
        'quantity',
        'upc',
        'asin',
        'mpn',
        'cost',
        'fn_sku',
        'lpn',
        'images',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'organization_id' => 'integer',
        'manifest_id' => 'integer',
        'price' => MoneyCast::class,
        'cost' => MoneyCast::class,
    ];

    // RELATIONSHIPS

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function manifest()
    {
        return $this->belongsTo(\App\Models\Manifest::class);
    }
}
