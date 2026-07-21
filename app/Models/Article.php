<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'image',
        'alt',
        'category',
        'tag',
        'published_at',
        'is_active',
        'read_count',
        'hero_caption',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_active' => 'boolean',
        'read_count' => 'integer',
    ];
}
