<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parcela extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_PREVISTA  = 'prevista';
    public const STATUS_RECEBIDA  = 'recebida';
    public const STATUS_ATRASADA  = 'atrasada';
    public const STATUS_BLOQUEADA = 'bloqueada';

    protected $fillable = [
        'tenant_id',
        'termo_id',
        'numero',
        'valor',
        'previsto_em',
        'recebido_em',
        'status',
        'comprovante_url',
    ];

    protected $casts = [
        'valor'       => 'decimal:2',
        'previsto_em' => 'date',
        'recebido_em' => 'date',
        'numero'      => 'integer',
    ];

    public function termo(): BelongsTo
    {
        return $this->belongsTo(TermoColaboracao::class, 'termo_id');
    }
}
