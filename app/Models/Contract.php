<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use App\Traits\BelongsToTenant;

class Contract extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'kind',
        'content',
        'signer_name',
        'signer_email',
        'signer_address',
        'signer_phone',
        'signer_cpf',
        'signer_rg',
        'signer_ip',
        'signer_user_agent',
        'token',
        'status',
        'signature_image',
        'signed_at',
        'public_sign_expires_at',
        'public_viewed_at',
        'document_hash',
        'signature_hash',
    ];

    protected $casts = [
        'signed_at'              => 'datetime',
        'public_sign_expires_at' => 'datetime',
        'public_viewed_at'       => 'datetime',
        'signer_name'            => 'encrypted',
        'signer_email'           => 'encrypted',
        'signer_phone'           => 'encrypted',
        'signer_cpf'             => 'encrypted',
        'signer_rg'              => 'encrypted',
    ];

    protected $hidden = ['token_bidx'];

    // ── Token publico de assinatura — encrypted-at-rest + bidx pesquisavel ───
    // Mesmo padrao de NgoDonor::portal_token e Transaction::public_receipt_token.

    public function getTokenAttribute(?string $value): ?string
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

    public function setTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['token']      = $value;
            $this->attributes['token_bidx'] = null;
            return;
        }
        $this->attributes['token']      = Crypt::encryptString($value);
        $this->attributes['token_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }
}
