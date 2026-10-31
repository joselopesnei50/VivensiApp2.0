<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Template WhatsApp Cloud API (per-tenant, cache local sincronizado da Meta).
 *
 * Cada linha representa 1 template gerenciado pelo tenant via UI Vivensi,
 * espelhando a WABA correspondente na Graph API. Multi-tenancy garantida
 * pelo trait BelongsToTenant + FK direto pra whatsapp_instances (cascadeOnDelete).
 */
class WhatsappTemplate extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_LOCAL_DRAFT = 'LOCAL_DRAFT';
    public const STATUS_PENDING     = 'PENDING';
    public const STATUS_APPROVED    = 'APPROVED';
    public const STATUS_REJECTED    = 'REJECTED';
    public const STATUS_PAUSED      = 'PAUSED';
    public const STATUS_DISABLED    = 'DISABLED';

    public const CATEGORY_MARKETING      = 'MARKETING';
    public const CATEGORY_UTILITY        = 'UTILITY';
    public const CATEGORY_AUTHENTICATION = 'AUTHENTICATION';

    protected $fillable = [
        'tenant_id',
        'whatsapp_instance_id',
        'waba_id',
        'meta_template_id',
        'name',
        'language',
        'category',
        'status',
        'rejection_reason',
        'synced_at',
        'components',
        'variable_samples',
    ];

    protected $casts = [
        'components'       => 'array',
        'variable_samples' => 'array',
        'synced_at'        => 'datetime',
    ];

    public function instance()
    {
        return $this->belongsTo(WhatsappInstance::class, 'whatsapp_instance_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_LOCAL_DRAFT, self::STATUS_REJECTED], true);
    }

    public function isSendable(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Extrai o texto do body do template pra preview rápido.
     * Retorna o corpo com placeholders literais ({{1}}, {{2}}, ...).
     */
    public function bodyText(): ?string
    {
        foreach ($this->components ?? [] as $c) {
            if (($c['type'] ?? '') === 'BODY') {
                return $c['text'] ?? null;
            }
        }
        return null;
    }

    /**
     * Extrai o texto do footer do template.
     */
    public function footerText(): ?string
    {
        foreach ($this->components ?? [] as $c) {
            if (($c['type'] ?? '') === 'FOOTER') {
                return $c['text'] ?? null;
            }
        }
        return null;
    }

    /**
     * Renderiza o corpo do template substituindo {{1}}, {{2}}... pelos valores
     * da lista indexada (mesma convencao da Meta: $vars[0] -> {{1}}).
     *
     * Fallback: se um placeholder nao tem valor correspondente, mantem a chave
     * literal pra expor o problema em vez de silenciar com string vazia.
     */
    public function renderBody(array $vars = []): ?string
    {
        $body = $this->bodyText();
        if ($body === null || $body === '') {
            return $body;
        }
        return preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function ($m) use ($vars) {
            $idx = (int) $m[1] - 1; // {{1}} -> $vars[0]
            return array_key_exists($idx, $vars) ? (string) $vars[$idx] : $m[0];
        }, $body);
    }
}
