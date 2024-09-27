<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignorInvoice extends Model
{
    use HasFactory;

    protected $appends = ['consignor_name'];

    protected $fillable = [
        'id',
        'organization_id',
        'consignor_id',
        'amount_paid',
        'amount_collected'
    ];

    protected $casts = [
        'id' => 'integer',
        'organization_id' => 'integer',
        'consignor_id' => 'integer',
        'amount_paid' => 'integer',
        'amount_collected' => 'integer'
    ];

    // RELATIONSHIPS

    public function consignor()
    {
        return $this->belongsTo(\App\Models\Consignor::class);
    }

    // ATTRIBUTES
    public function getConsignorNameAttribute()
    {
        return $this->consignor->name;
    }
}
