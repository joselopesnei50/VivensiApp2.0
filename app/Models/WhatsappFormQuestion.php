<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappFormQuestion extends Model
{
    use HasFactory;

    public const TYPES = ['text', 'number', 'yes_no', 'buttons', 'list'];

    protected $fillable = [
        'form_id',
        'position',
        'field_key',
        'text',
        'type',
        'options',
        'required',
        'validation_regex',
        'min_value',
        'max_value',
    ];

    protected $casts = [
        'options'   => 'array',
        'required'  => 'boolean',
        'min_value' => 'integer',
        'max_value' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(WhatsappForm::class, 'form_id');
    }
}
