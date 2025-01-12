<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'header',
        'body',
        'footer',
        'read',
    ];

    protected $casts = [
        'read' => 'boolean'
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
