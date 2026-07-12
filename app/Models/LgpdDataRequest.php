<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Solicitacoes LGPD do titular dos dados (Lei 13.709/2018).
 *
 * Tipos:
 *  - export  → art. 18 IV (portabilidade). Gera ZIP com JSON de todos dados.
 *  - delete  → art. 15 (eliminacao). Grace 30d antes do purge definitivo.
 *  - rectify → art. 18 III (correcao). Reservado para v2.
 *  - restrict → art. 18 IV (bloqueio). Reservado para v2.
 *
 * Status:
 *  - pending    → aguardando processamento (job automatico ou admin)
 *  - processing → job em execucao
 *  - completed  → concluida (arquivo pronto ou usuario anonimizado)
 *  - rejected   → admin rejeitou com justificativa em notes
 */
class LgpdDataRequest extends Model
{
    use BelongsToTenant;

    public const TYPE_EXPORT   = 'export';
    public const TYPE_DELETE   = 'delete';
    public const TYPE_RECTIFY  = 'rectify';
    public const TYPE_RESTRICT = 'restrict';

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_REJECTED   = 'rejected';

    /** Grace period padrao antes de executar deletion (dias). */
    public const DELETION_GRACE_DAYS = 30;

    /** TTL do token de download apos gerado (horas). */
    public const EXPORT_TOKEN_TTL_HOURS = 48;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'type',
        'status',
        'ip_address',
        'notes',
        'processed_at',
        'processed_by',
        'scheduled_for',
        'cancelled_at',
        'export_token',
        'export_expires_at',
        'export_file_path',
        'export_download_count',
    ];

    protected $casts = [
        'processed_at'          => 'datetime',
        'scheduled_for'         => 'datetime',
        'cancelled_at'          => 'datetime',
        'export_expires_at'     => 'datetime',
        'export_download_count' => 'integer',
    ];

    // ── Relacionamentos ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeExports($query)
    {
        return $query->where('type', self::TYPE_EXPORT);
    }

    public function scopeDeletions($query)
    {
        return $query->where('type', self::TYPE_DELETE);
    }

    /**
     * Deletions que ja passaram do grace period e precisam ser executadas.
     */
    public function scopeDueForPurge($query)
    {
        return $query->where('type', self::TYPE_DELETE)
            ->where('status', self::STATUS_PENDING)
            ->whereNull('cancelled_at')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now());
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isExport(): bool
    {
        return $this->type === self::TYPE_EXPORT;
    }

    public function isDeletion(): bool
    {
        return $this->type === self::TYPE_DELETE;
    }

    public function isCancellable(): bool
    {
        return $this->isDeletion()
            && $this->status === self::STATUS_PENDING
            && $this->cancelled_at === null
            && $this->scheduled_for
            && $this->scheduled_for->isFuture();
    }

    public function isTokenValid(): bool
    {
        return $this->export_token
            && $this->export_expires_at
            && $this->export_expires_at->isFuture()
            && $this->export_file_path;
    }

    /**
     * Gera token opaco (base64url de 256 bits) + define expiracao.
     */
    public function generateExportToken(): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $this->update([
            'export_token'      => $token,
            'export_expires_at' => now()->addHours(self::EXPORT_TOKEN_TTL_HOURS),
        ]);

        return $token;
    }

    /**
     * Retorna dias restantes ate a deletion ser executada.
     * Negativo se ja passou (nao deveria — job de purge processa antes).
     */
    public function daysUntilPurge(): ?int
    {
        if (!$this->scheduled_for) {
            return null;
        }
        return (int) now()->diffInDays($this->scheduled_for, false);
    }
}
