<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use App\Traits\BelongsToTenant;

class Transaction extends Model
{
    use \App\Traits\Auditable, BelongsToTenant;
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
        'volunteer_id'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'public_receipt_expires_at' => 'datetime',
    ];

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

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
