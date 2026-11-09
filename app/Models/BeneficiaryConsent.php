<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro (assinatura ou revogação) do termo de consentimento LGPD de um
 * beneficiário. Só é criado pelo App\Services\ConsentService.
 */
class BeneficiaryConsent extends Model
{
    use BelongsToTenant;

    public const ACTION_GRANTED = 'granted';
    public const ACTION_REVOKED = 'revoked';

    public const ROLES = [
        'titular'           => 'Titular',
        'responsavel_legal' => 'Responsável legal',
    ];

    protected $fillable = [
        'tenant_id', 'beneficiary_id', 'action', 'signer_name', 'signer_role',
        'image_use', 'signed_at', 'term_version', 'recorded_by', 'notes',
    ];

    protected $casts = [
        'image_use' => 'boolean',
        'signed_at' => 'date',
    ];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->signer_role] ?? '—';
    }
}
