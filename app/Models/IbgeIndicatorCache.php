<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IbgeIndicatorCache extends Model
{
    use HasFactory;

    protected $table = 'ibge_indicators_cache';

    protected $fillable = [
        'city_ibge_code',
        'city_name',
        'indicator_key',
        'value',
        'year'
    ];
}
