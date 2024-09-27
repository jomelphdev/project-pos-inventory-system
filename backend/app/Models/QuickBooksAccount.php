<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuickBooksAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'quickbooks_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'store_id',
        'quickbooks_account_id',
        'account_type'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'store_id' => 'integer',
        'quickbooks_account_id' => 'integer',
    ];

    // RELATIONSHIPS

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
