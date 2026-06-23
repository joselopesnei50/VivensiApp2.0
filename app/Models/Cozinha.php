<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cozinha extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    public const MODALIDADE_DIRETA   = 'direta';
    public const MODALIDADE_INDIRETA = 'indireta';

    public const STATUS_ATIVA   = 'ativa';
    public const STATUS_INATIVA = 'inativa';

    protected $fillable = [
        'tenant_id',
        'termo_id',
        'nome',
        'meta_refeicoes_mes',
        'modalidade_execucao',
        'status',
        'address_zip',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_city',
        'address_state',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'meta_refeicoes_mes' => 'integer',
        'latitude'           => 'decimal:8',
        'longitude'          => 'decimal:8',
    ];

    public function termo(): BelongsTo
    {
        return $this->belongsTo(TermoColaboracao::class, 'termo_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function coordenadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cozinha_coordenadores')
            ->using(CozinhaCoordenador::class)
            ->withPivot(['papel', 'ativo', 'tenant_id'])
            ->withTimestamps();
    }

    public function scopeAtiva($query)
    {
        return $query->where('status', self::STATUS_ATIVA);
    }
}
