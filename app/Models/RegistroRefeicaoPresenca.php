<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Presença de beneficiário em refeição. CPF tratado igual ao Beneficiary
 * (encryption + blind index). LGPD §5: nunca serializar plaintext em logs.
 */
class RegistroRefeicaoPresenca extends Model
{
    use HasFactory;

    protected $table = 'registros_refeicao_presencas';

    protected $fillable = [
        'registro_id',
        'beneficiary_id',
        'nome',
        'cpf',
        'cpf_bidx',
    ];

    protected $hidden = ['cpf', 'cpf_bidx'];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroRefeicao::class, 'registro_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    // ── Encryption accessors/mutators (mesmo padrão Beneficiary) ──────────────

    public function getCpfAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public function setCpfAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['cpf']      = $value;
            $this->attributes['cpf_bidx'] = null;
            return;
        }
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        $this->attributes['cpf']      = Crypt::encryptString($digits);
        $this->attributes['cpf_bidx'] = hash_hmac('sha256', $digits, config('app.key'));
    }
}
