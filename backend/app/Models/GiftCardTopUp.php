<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardTopUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'action',
        'gift_card_id'
    ];
}
