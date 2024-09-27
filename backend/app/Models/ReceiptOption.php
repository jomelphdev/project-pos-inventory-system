<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReceiptOption extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'preference_id',
        'name',
        'image_url',
        'footer',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'preference_id' => 'integer',
    ];


    public function stores()
    {
        return $this->belongsToMany(\App\Models\Store::class);
    }

    public function preferences()
    {
        return $this->belongsTo(\App\Models\Preference::class);
    }
}
