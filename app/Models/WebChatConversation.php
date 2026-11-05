<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Conversa do chat do site. O visitante se identifica pelo public_id + um
 * segredo guardado no navegador dele (só o hash fica no banco).
 * Sem tenant: é o atendimento comercial do próprio Vivensi (super admin).
 */
class WebChatConversation extends Model
{
    public const STATUS_BOT           = 'bot';
    public const STATUS_WAITING_HUMAN = 'waiting_human';
    public const STATUS_HUMAN         = 'human';
    public const STATUS_CLOSED        = 'closed';

    public const STATUS_LABELS = [
        self::STATUS_BOT           => 'Com o Bruno',
        self::STATUS_WAITING_HUMAN => 'Aguardando equipe',
        self::STATUS_HUMAN         => 'Com a equipe',
        self::STATUS_CLOSED        => 'Encerrada',
    ];

    protected $fillable = [
        'public_id', 'secret_hash', 'status', 'visitor_name', 'visitor_email', 'visitor_phone',
        'page_url', 'page_title', 'source', 'device', 'ip_address', 'user_agent',
        'assigned_user_id', 'meeting_booking_id', 'visitor_messages', 'ai_replies',
        'unread_for_team', 'last_message_at', 'human_requested_at', 'closed_at',
    ];

    protected $hidden = ['secret_hash', 'ip_address', 'user_agent'];

    protected $casts = [
        'last_message_at'    => 'datetime',
        'human_requested_at' => 'datetime',
        'closed_at'          => 'datetime',
    ];

    /** Cria a conversa e devolve o segredo em texto puro (só existe neste momento). */
    public static function open(array $attributes): array
    {
        $secret = Str::random(48);
        $conversation = static::create($attributes + [
            'public_id'   => (string) Str::uuid(),
            'secret_hash' => hash('sha256', $secret),
            'status'      => self::STATUS_BOT,
        ]);

        return [$conversation, $secret];
    }

    public function checkSecret(?string $secret): bool
    {
        return is_string($secret) && $secret !== '' && hash_equals($this->secret_hash, hash('sha256', $secret));
    }

    /** Gera um novo segredo (retomada por link de e-mail). */
    public function rotateSecret(): string
    {
        $secret = Str::random(48);
        $this->forceFill(['secret_hash' => hash('sha256', $secret)])->save();
        return $secret;
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WebChatMessage::class)->orderBy('id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(MeetingBooking::class, 'meeting_booking_id');
    }

    public function botIsActive(): bool
    {
        return $this->status === self::STATUS_BOT;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function displayName(): string
    {
        return $this->visitor_name ?: ($this->visitor_email ?: 'Visitante #' . $this->id);
    }

    public function addMessage(string $sender, string $body, array $meta = [], ?int $userId = null): WebChatMessage
    {
        $msg = $this->messages()->create([
            'sender'  => $sender,
            'user_id' => $userId,
            'body'    => $body,
            'meta'    => $meta ?: null,
        ]);

        // Incremento atômico: visitante e job do Bruno podem gravar ao mesmo tempo
        $updates = ['last_message_at' => now()];
        if ($sender === WebChatMessage::SENDER_VISITOR) {
            $updates['visitor_messages'] = \Illuminate\Support\Facades\DB::raw('visitor_messages + 1');
            $updates['unread_for_team']  = \Illuminate\Support\Facades\DB::raw('unread_for_team + 1');
        }
        static::whereKey($this->id)->update($updates);
        $this->refresh();

        return $msg;
    }
}
