<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailContact extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_ACTIVE       = 'active';
    public const STATUS_BOUNCED      = 'bounced';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_INVALID      = 'invalid';

    protected $fillable = [
        'email_contact_list_id',
        'tenant_id',
        'email',
        'name',
        'status',
        'source',
        'added_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'added_at'        => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(EmailContactList::class, 'email_contact_list_id');
    }

    /**
     * Safety net LGPD art. 8/18 (2026-09-29) — retorna emails do tenant que
     * estao com status='unsubscribed' em QUALQUER lista. Usado como ultimo
     * filtro em resolveRecipients dos 3 paineis (NGO/Admin/Manager) pra
     * garantir que descadastro via link no rodape do e-mail seja respeitado
     * mesmo quando o audience_type nao passa pelo activeContacts()
     * (leads, donors mixed, manual, csv reimportado, etc).
     *
     * @param  int      $tenantId  0 = todos os tenants (usado no painel admin SaaS)
     * @param  string[] $emails    lista bruta de emails candidatos
     * @return array<string,true>  lookup map lowercased dos emails bloqueados
     */
    public static function unsubscribedLookup(int $tenantId, array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $normalized = array_values(array_unique(array_map(
            fn ($e) => mb_strtolower(trim((string) $e)),
            $emails
        )));

        $q = static::query()
            ->withoutGlobalScope('tenant') // safety net roda antes do auth loop
            ->where('status', self::STATUS_UNSUBSCRIBED)
            ->whereIn('email', $normalized);

        if ($tenantId > 0) {
            $q->where('tenant_id', $tenantId);
        }

        return $q->pluck('email')
            ->map(fn ($e) => mb_strtolower((string) $e))
            ->flip()
            ->all();
    }
}
