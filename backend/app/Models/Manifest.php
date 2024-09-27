<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manifest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'manifest_name',
        'ended_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'organization_id' => 'integer',
    ];

    // RELATIONSHIPS

    public function manifestItems()
    {
        return $this->hasMany(\App\Models\ManifestItem::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
}


