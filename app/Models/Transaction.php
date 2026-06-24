<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

use App\Traits\BelongsToTenant;

class Transaction extends Model
{
    use \App\Traits\Auditable, BelongsToTenant, SoftDeletes;
    use HasFactory;

    private static function publicReceiptTtlDays(): ?int
    {
        $ttl = config('receipts.public_link_ttl_days', 30);
        return $ttl === null ? null : (int) $ttl;
    }

    protected $table = 'transactions';

    protected static function booted(): void
    {
        static::creating(function (self $transaction) {
            if (!$transaction->public_receipt_token && ($transaction->type ?? null) === 'income') {
                $transaction->public_receipt_token = (string) Str::uuid();
            }

            if (($transaction->type ?? null) === 'income' && !$transaction->public_receipt_expires_at) {
                $ttlDays = self::publicReceiptTtlDays();
                $transaction->public_receipt_expires_at = $ttlDays === null ? null : now()->addDays($ttlDays);
            }

            if (($transaction->type ?? null) === 'income' && !$transaction->receipt_auth_code) {
                $transaction->receipt_auth_code = self::generateReceiptAuthCode();
            }
        });
    }

    private static function generateReceiptAuthCode(): string
    {
        // 16 hex chars (8 bytes) - easy to type, case-insensitive.
        // Ensure uniqueness across ALL tenants by bypassing global scopes.
        for ($i = 0; $i < 5; $i++) {
            $code = strtoupper(bin2hex(random_bytes(8)));

            $exists = self::withoutGlobalScopes()
                ->where('receipt_auth_code', $code)
                ->exists();

            if (!$exists) {
                return $code;
            }
        }

        throw new \RuntimeException('Não foi possível gerar um código de validação único para o recibo.');
    }

    protected $fillable = [
        'public_receipt_token',
        'public_receipt_expires_at',
        'receipt_auth_code',
        'tenant_id',
        'category_id',
        'project_id',
        'ngo_donor_id',
        'client_id',
        'description',
        'amount',
        'type',
        'date',
        'status',
        'attachment_path',
        'external_id',
        'origem_verba_id',
        'receipt_path',
        'approval_status',
        'volunteer_id',
        'gateway_id',
        'paid_at',
        'plan_id',
        'ofx_fitid',
        'reconciled_at',
        'nfse_numero',
        'nfse_url_pdf',
        'nfse_emitida_em',
    ];

    protected $casts = [
        'date'           => 'date',
        'paid_at'        => 'datetime',
        'reconciled_at'  => 'datetime',
        'amount'         => 'decimal:2',
        'public_receipt_expires_at' => 'datetime',
        'nfse_emitida_em' => 'date',
    ];

    // bidx nunca pode vazar (e o hash usado em lookups); o token plaintext
    // continua exposto via accessor para construcao de URL.
    protected $hidden = ['public_receipt_token_bidx'];

    // ── Token de recibo publico — encrypted-at-rest + bidx pesquisavel ────────
    // Mesmo padrao de NgoDonor::portal_token.

    public function getPublicReceiptTokenAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Fallback para dados ainda nao migrados ou app.key rotacionada.
            return $value;
        }
    }

    public function setPublicReceiptTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['public_receipt_token']      = $value;
            $this->attributes['public_receipt_token_bidx'] = null;
            return;
        }
        $this->attributes['public_receipt_token']      = Crypt::encryptString($value);
        $this->attributes['public_receipt_token_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }

    public function category() {
        return $this->belongsTo(\App\Models\FinancialCategory::class, 'category_id');
    }

    public function project() {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function donor()
    {
        return $this->belongsTo(\App\Models\NgoDonor::class, 'ngo_donor_id');
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class, 'client_id');
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
