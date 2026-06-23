<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodoFechado extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'periodos_fechados';

    protected $fillable = [
        'tenant_id',
        'cozinha_id',
        'mes',
        'fechado_em',
        'fechado_por_user_id',
        'content_hash_total',
        'registros_count',
        'refeicoes_total',
    ];

    protected $casts = [
        'mes'             => 'date',
        'fechado_em'      => 'datetime',
        'registros_count' => 'integer',
        'refeicoes_total' => 'integer',
    ];

    public function cozinha(): BelongsTo
    {
        return $this->belongsTo(Cozinha::class);
    }

    public function fechador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fechado_por_user_id');
    }
}
