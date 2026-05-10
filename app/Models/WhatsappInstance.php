<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * WhatsappInstance
 *
 * ISOLAMENTO MULTI-TENANT: este model usa BelongsToTenant (global scope por Auth) +
 * scopeForTenant() (scope explícito para webhooks e jobs onde Auth não está ativo).
 * Em webhooks sempre use WhatsappInstance::where('instance_token', $token)->first()
 * ou WhatsappInstance::forTenant($id) — nunca ::find() diretamente.
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
        'instance_token', // Nunca expor o token de autenticação da Evolution API em JSON
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

    // ── Accessors para compatibilidade com EvolutionApiService ─────────────

    /**
     * Mapeia instance_name para o atributo esperado pelo EvolutionApiService.
     */
    public function getEvolutionInstanceNameAttribute(): string
    {
        return $this->instance_name ?? '';
    }

    /**
     * Mapeia instance_token para o atributo esperado pelo EvolutionApiService.
     */
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
