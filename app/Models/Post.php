<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'is_published',
        'published_at',
        'excerpt',
        'meta_description',
        'tags',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getContentHtmlAttribute(): string
    {
        return \Illuminate\Support\Str::markdown($this->content ?? '', [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
