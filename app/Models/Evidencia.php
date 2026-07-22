<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Evidencia extends Model
{
    use HasFactory;

    protected $table = 'evidencias';

    protected $fillable = [
        'avaliacao_requisito_id',
        'evidenciavel_type',
        'evidenciavel_id',
        'tipo',
        'descricao',
    ];

    public function avaliacao(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AvaliacaoRequisito::class, 'avaliacao_requisito_id');
    }

    public function evidenciavel(): MorphTo
    {
        return $this->morphTo();
    }
}
