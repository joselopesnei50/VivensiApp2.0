<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * WhatsappInstance
 *
 * ISOLAMENTO MULTI-TENANT: este model usa BelongsToTenant (global scope por Auth) +
 * scopeForTenant() (scope explícito para webhooks e jobs onde Auth não está ativo).
 * Em webhooks use WhatsappInstance::where('instance_token_bidx', hash_hmac(..., $token))->first()
 * ou WhatsappInstance::forTenant($id) — nunca ::find() diretamente.
 *
 * instance_token é armazenado cifrado (AES-256). instance_token_bidx é o HMAC-SHA256
 * do token em plaintext — usado como índice para lookup sem expor o valor real.
 */
class WhatsappInstance extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'instance_name',
        'instance_token',
        'status',
        'phone_number',
        'owner_jid',
        'safe_window_start',
        'safe_window_end',
        'timezone',
        'daily_limit',
        'messages_sent_today',
        'daily_reset_at',
        'settings',
    ];

    protected $hidden = [
        'instance_token',
        'instance_token_bidx',
    ];

    protected $casts = [
        'settings'        => 'array',
        'daily_reset_at'  => 'datetime',
        'daily_limit'     => 'integer',
        'messages_sent_today' => 'integer',
    ];

    // ── Relacionamentos ─────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaignMessages()
    {
        return $this->hasMany(WhatsappCampaignMessage::class, 'instance_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ── Métodos de Negócio ──────────────────────────────────────────────────

    // ── Encryption accessors/mutators ────────────────────────────────────────

    public function getInstanceTokenAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // Plaintext legacy row — return as-is
        }
    }

    public function setInstanceTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['instance_token']      = $value;
            $this->attributes['instance_token_bidx'] = null;
            return;
        }
        $this->attributes['instance_token']      = Crypt::encryptString($value);
        $this->attributes['instance_token_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }

    // ── Accessors para compatibilidade com EvolutionApiService ─────────────

    public function getEvolutionInstanceNameAttribute(): string
    {
        return $this->instance_name ?? '';
    }

    public function getEvolutionInstanceTokenAttribute(): ?string
    {
        return $this->instance_token;
    }

    // ── Métodos de Negócio ──────────────────────────────────────────────────

    /**
     * Gera nome único de instância para um tenant.
     * Formato: vivensi_t{tenant_id}_{random6}
     */
    public static function generateInstanceName(int $tenantId): string
    {
        return 'vivensi_t' . $tenantId . '_' . Str::random(6);
    }

    /**
     * Verifica se está dentro da janela de horário seguro.
     */
    public function isWithinSafeWindow(): bool
    {
        $now   = now()->timezone($this->timezone ?? 'America/Sao_Paulo');
        $start = \Carbon\Carbon::createFromTimeString($this->safe_window_start ?? '08:00', $this->timezone ?? 'America/Sao_Paulo');
        $end   = \Carbon\Carbon::createFromTimeString($this->safe_window_end ?? '21:00', $this->timezone ?? 'America/Sao_Paulo');

        return $now->between($start, $end);
    }

    /**
     * Verifica se o limite diário foi atingido, resetando se for um novo dia.
     */
    public function hasReachedDailyLimit(): bool
    {
        // Reseta o contador se for um novo dia
        if (!$this->daily_reset_at || $this->daily_reset_at->isYesterday() || $this->daily_reset_at->lt(today())) {
            $this->update([
                'messages_sent_today' => 0,
                'daily_reset_at'      => now(),
            ]);
        }

        return $this->messages_sent_today >= $this->daily_limit;
    }

    /**
     * Incrementa o contador de mensagens enviadas hoje.
     */
    public function incrementDailyCount(): void
    {
        $this->increment('messages_sent_today');
    }

    /**
     * Verifica se a instância pode enviar uma mensagem agora.
     */
    public function canSend(): bool
    {
        return $this->status === 'open'
            && $this->isWithinSafeWindow()
            && !$this->hasReachedDailyLimit();
    }
}
