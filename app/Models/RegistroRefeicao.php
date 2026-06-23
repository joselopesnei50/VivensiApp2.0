<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistroRefeicao extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'registros_refeicao';

    public const TIPO_ALMOCO  = 'almoco';
    public const TIPO_JANTA   = 'janta';
    public const TIPO_SOPA    = 'sopa';
    public const TIPO_MARMITA = 'marmita';
    public const TIPO_OUTRO   = 'outro';

    public const STATUS_PENDENTE  = 'pendente';
    public const STATUS_VALIDO    = 'valido';
    public const STATUS_ESTORNADO = 'estornado';

    protected $fillable = [
        'tenant_id',
        'cozinha_id',
        'meta_id',
        'data_servico',
        'datetime_registrado',
        'tipo',
        'quantidade',
        'latitude',
        'longitude',
        'status',
        'content_hash',
        'registered_by',
        'observacao',
    ];

    protected $casts = [
        'data_servico'        => 'date',
        'datetime_registrado' => 'datetime',
        'quantidade'          => 'integer',
        'latitude'            => 'decimal:8',
        'longitude'           => 'decimal:8',
    ];

    public function cozinha(): BelongsTo
    {
        return $this->belongsTo(Cozinha::class);
    }

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(RegistroRefeicaoFoto::class, 'registro_id');
    }

    public function presencas(): HasMany
    {
        return $this->hasMany(RegistroRefeicaoPresenca::class, 'registro_id');
    }

    public function estornos(): HasMany
    {
        return $this->hasMany(EstornoRefeicao::class, 'registro_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function isValido(): bool
    {
        return $this->status === self::STATUS_VALIDO;
    }

    public function isEstornado(): bool
    {
        return $this->status === self::STATUS_ESTORNADO;
    }

    public function mesServico(): string
    {
        return $this->data_servico->format('Y-m-01');
    }
}
