<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    public const STATUSES = [
        'draft'     => 'Rascunho',
        'sent'      => 'Enviado',
        'accepted'  => 'Aceito',
        'rejected'  => 'Rejeitado',
        'expired'   => 'Expirado',
        'converted' => 'Convertido em receita',
    ];

    protected $fillable = [
        'tenant_id',
        'client_id',
        'quote_number',
        'title',
        'status',
        'issue_date',
        'valid_until',
        'discount',
        'total',
        'notes',
        'terms',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'converted_at',
        'converted_transaction_id',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'valid_until'  => 'date',
        'discount'     => 'decimal:2',
        'total'        => 'decimal:2',
        'sent_at'      => 'datetime',
        'accepted_at'  => 'datetime',
        'rejected_at'  => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position');
    }

    public function convertedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'converted_transaction_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format((float) $this->total, 2, ',', '.');
    }

    public function getSubtotalItemsAttribute(): float
    {
        return (float) $this->items->sum('subtotal');
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && !in_array($this->status, ['accepted', 'rejected', 'converted']);
    }

    /**
     * Recalcula o total (subtotal dos itens - desconto). Não persiste — o caller decide.
     */
    public function recalculateTotal(): float
    {
        $subtotal = (float) $this->items()->sum('subtotal');
        $total    = max(0, $subtotal - (float) $this->discount);
        $this->total = $total;
        return $total;
    }

    /**
     * Gera o próximo número no formato PROP-YYYY-NNNN para o tenant.
     */
    public static function nextQuoteNumber(int $tenantId): string
    {
        $year   = now()->format('Y');
        $prefix = "PROP-{$year}-";

        $last = self::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('quote_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('quote_number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
