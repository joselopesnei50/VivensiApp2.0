<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransparencyPortal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'slug', 'title', 'cnpj', 'mission', 'vision', 'values', 
        'sic_email', 'sic_phone', 'settings', 'is_published'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_published' => 'boolean'
    ];
}
