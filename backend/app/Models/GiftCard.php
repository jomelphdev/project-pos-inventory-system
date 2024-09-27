<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_code',
        'title',
        'description',
        'is_activated',
        'balance',
        'expiration_date',
        'created_by'
    ];


    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id');
    }

    public function giftCardStore()
    {
        return $this->hasMany(\App\Models\GiftCardStore::class);
    }
}
