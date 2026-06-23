<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstornoRefeicao extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'estornos_refeicao';

    public const STATUS_PENDENTE  = 'pendente';
    public const STATUS_APROVADO  = 'aprovado';
    public const STATUS_REJEITADO = 'rejeitado';

    protected $fillable = [
        'tenant_id',
        'registro_id',
        'motivo',
        'evidencia_url',
        'solicitado_por_user_id',
        'aprovado_por_user_id',
        'aprovado_em',
        'status',
    ];

    protected $casts = [
        'aprovado_em' => 'datetime',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroRefeicao::class, 'registro_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_user_id');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por_user_id');
    }

    public function isPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    public function isAprovado(): bool
    {
        return $this->status === self::STATUS_APROVADO;
    }
}
