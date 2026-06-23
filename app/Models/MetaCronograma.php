<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cronograma mensal da Meta. Não usa BelongsToTenant — escopo de tenant vem
 * via Meta (sempre acessar com `->cronograma` na Meta). Acesso direto fora do
 * agregado deve filtrar pelo meta_id, que já é escopado.
 */
class MetaCronograma extends Model
{
    use HasFactory;

    protected $table = 'metas_cronograma';

    protected $fillable = [
        'meta_id',
        'mes',
        'quantidade',
        'valor',
    ];

    protected $casts = [
        'mes'        => 'date',
        'quantidade' => 'decimal:4',
        'valor'      => 'decimal:2',
    ];

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class);
    }
}
