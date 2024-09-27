<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemHistory extends Model
{
    use HasFactory;

    protected $table = 'item_histories';

    protected $fillable = [
        'item_id',
        'store_id',
        'old_price',
        'new_price',
        'old_original_price',
        'new_original_price',
        'old_cost',
        'new_cost',
        'reason_for_change',
        'action',
        'created_by'
    ];
}
