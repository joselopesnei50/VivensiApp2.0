<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitoLegal extends Model
{
    use HasFactory;

    protected $table = 'requisitos_legais';

    protected $fillable = [
        'codigo',
        'eixo',
        'area',
        'titulo',
        'enunciado',
        'base_legal',
        'tipo',
        'periodicidade',
        'risco',
        'vigente_de',
        'vigente_ate',
        'ativo',
    ];

    protected $casts = [
        'vigente_de'  => 'date',
        'vigente_ate' => 'date',
        'ativo'       => 'boolean',
    ];

    public function regra(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RegraAvaliacao::class, 'requisito_legal_id');
    }

    public function avaliacoes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AvaliacaoRequisito::class, 'requisito_legal_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeDoEixo($query, string $eixo)
    {
        return $query->where('eixo', $eixo);
    }

    public function scopeDoTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
