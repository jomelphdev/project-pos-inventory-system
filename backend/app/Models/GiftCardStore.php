<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_card_id',
        'store_id'
    ];
}
