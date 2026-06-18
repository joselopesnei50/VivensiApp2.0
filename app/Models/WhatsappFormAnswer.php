<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappFormAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'question_id',
        'field_key',
        'answer_text',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WhatsappFormSession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(WhatsappFormQuestion::class, 'question_id');
    }
}
