<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\BeneficiaryConsent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Termo de consentimento LGPD do beneficiário: registra assinatura e
 * revogação, mantendo o histórico e a situação atual no cadastro.
 *
 * Menor de 18 anos (pela data de nascimento) só pode ter o termo assinado
 * pelo responsável legal (LGPD, art. 14).
 */
class ConsentService
{
    /** Versão do texto do termo (muda quando o texto mudar). */
    public const TERM_VERSION = '2026-11';

    public function isMinor(Beneficiary $b, ?Carbon $on = null): bool
    {
        return $b->birth_date !== null && $b->birth_date->diffInYears($on ?? now()) < 18;
    }

    public function grant(Beneficiary $b, array $data, ?int $userId): BeneficiaryConsent
    {
        $role = $data['signer_role'] ?? 'titular';
        $signer = trim((string) ($data['signer_name'] ?? ''));
        if ($role === 'titular' && $signer === '') {
            $signer = $b->name;
        }
        $signedAt = Carbon::parse($data['signed_at'] ?? now())->startOfDay();
        $image = (bool) ($data['image_use'] ?? false);

        return DB::transaction(function () use ($b, $role, $signer, $signedAt, $image, $data, $userId) {
            $consent = BeneficiaryConsent::create([
                'tenant_id'      => $b->tenant_id,
                'beneficiary_id' => $b->id,
                'action'         => BeneficiaryConsent::ACTION_GRANTED,
                'signer_name'    => $signer,
                'signer_role'    => $role,
                'image_use'      => $image,
                'signed_at'      => $signedAt->toDateString(),
                'term_version'   => self::TERM_VERSION,
                'recorded_by'    => $userId,
                'notes'          => $data['notes'] ?? null,
            ]);

            $b->forceFill(['consent_at' => $signedAt->toDateString(), 'image_consent' => $image])->saveQuietly();

            return $consent;
        });
    }

    public function revoke(Beneficiary $b, ?string $notes, ?int $userId): BeneficiaryConsent
    {
        return DB::transaction(function () use ($b, $notes, $userId) {
            $consent = BeneficiaryConsent::create([
                'tenant_id'      => $b->tenant_id,
                'beneficiary_id' => $b->id,
                'action'         => BeneficiaryConsent::ACTION_REVOKED,
                'signed_at'      => now()->toDateString(),
                'term_version'   => self::TERM_VERSION,
                'recorded_by'    => $userId,
                'notes'          => $notes,
            ]);

            $b->forceFill(['consent_at' => null, 'image_consent' => false])->saveQuietly();

            return $consent;
        });
    }

    /** Regras de validação do formulário de assinatura. */
    public function rules(Beneficiary|null $b, bool $minor): array
    {
        return [
            'signer_role' => ['required', 'in:' . implode(',', array_keys(BeneficiaryConsent::ROLES)),
                function ($attr, $value, $fail) use ($minor) {
                    if ($minor && $value !== 'responsavel_legal') {
                        $fail('Beneficiário menor de 18 anos: o termo precisa ser assinado pelo responsável legal.');
                    }
                }],
            'signer_name' => 'required_if:signer_role,responsavel_legal|nullable|string|max:255',
            'signed_at'   => 'required|date|before_or_equal:today|after:2000-01-01',
            'image_use'   => 'nullable|boolean',
            'notes'       => 'nullable|string|max:1000',
        ];
    }
}
