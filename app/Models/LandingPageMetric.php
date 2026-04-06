<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPageMetric extends Model
{
    use HasFactory;

    protected $fillable = ['page_key', 'views', 'registrations', 'date'];

    /**
     * Increment metric for a specific page and day
     */
    public static function track($pageKey, $type = 'view')
    {
        $metric = self::firstOrCreate(
            ['page_key' => $pageKey, 'date' => now()->toDateString()],
            ['views' => 0, 'registrations' => 0]
        );

        if ($type === 'view') {
            $metric->increment('views');
        } elseif ($type === 'registration') {
            $metric->increment('registrations');
        }
    }
}
