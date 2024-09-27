<?php

namespace App\Models;

use App\Casts\UserFeedbackCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFeedback extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'prompt', 
        'feedback',
        'origin'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'organization_id' => 'integer',
        'user_id' => 'integer',
        'feedback' => UserFeedbackCast::class
    ];

    // RELATIONSHIPS

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
