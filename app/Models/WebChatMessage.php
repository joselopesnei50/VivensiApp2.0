<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebChatMessage extends Model
{
    public const SENDER_VISITOR = 'visitor';
    public const SENDER_BOT     = 'bot';
    public const SENDER_AGENT   = 'agent';
    public const SENDER_SYSTEM  = 'system';

    public $timestamps = false;

    protected $fillable = ['web_chat_conversation_id', 'sender', 'user_id', 'body', 'meta', 'created_at'];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WebChatConversation::class, 'web_chat_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Formato enviado ao navegador do visitante (sem dados internos). */
    public function toPublicArray(): array
    {
        return [
            'id'     => $this->id,
            'sender' => $this->sender,
            'name'   => match ($this->sender) {
                self::SENDER_BOT     => 'Bruno',
                self::SENDER_AGENT   => $this->user ? strtok((string) $this->user->name, ' ') . ' · Equipe Vivensi' : 'Equipe Vivensi',
                self::SENDER_VISITOR => 'Você',
                default              => null,
            },
            'body'   => $this->body,
            'kind'   => $this->meta['kind'] ?? null,
            'at'     => optional($this->created_at)->format('H:i'),
        ];
    }
}
