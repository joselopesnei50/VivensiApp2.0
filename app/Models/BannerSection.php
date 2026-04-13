<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerSection extends Model
{
    protected $fillable = ['banner_id', 'type', 'content', 'sort_order'];

    protected $casts = ['content' => 'array'];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}
