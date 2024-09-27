<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'sub_heading',
        'slug',
        'content',
        'image',
        'category',
        'is_published',
        'meta_title',
        'meta_description',
        'meta_image_alt',
        'deleted_at'
    ];
}
