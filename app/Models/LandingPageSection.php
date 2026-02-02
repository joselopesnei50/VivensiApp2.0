<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'type',
        'content',
        'sort_order'
    ];

    protected $casts = [
        'content' => 'array'
    ];

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }
}
