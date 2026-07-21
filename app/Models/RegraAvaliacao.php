<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegraAvaliacao extends Model
{
    use HasFactory;

    protected $table = 'regras_avaliacao';

    protected $fillable = [
        'requisito_legal_id',
        // Tipo A
        'modelo',
        'campo_filtro',
        'formula',
        'threshold',
        'threshold_tipo',
        'unidade',
        'threshold_editavel_admin',
        'threshold_editavel_tenant',
        // Tipo B
        'tipo_documento_obrigatorio',
        'alerta_dias_antes',
        // Tipo C
        'pergunta_declaracao',
        'requer_anexo',
    ];

    protected $casts = [
        'threshold'                 => 'decimal:2',
        'threshold_editavel_admin'  => 'boolean',
        'threshold_editavel_tenant' => 'boolean',
        'requer_anexo'              => 'boolean',
        'alerta_dias_antes'         => 'integer',
    ];

    public function requisito(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RequisitoLegal::class, 'requisito_legal_id');
    }
}
