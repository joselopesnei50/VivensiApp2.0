<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroRefeicaoFoto extends Model
{
    use HasFactory;

    protected $table = 'registros_refeicao_fotos';

    protected $fillable = [
        'registro_id',
        's3_path',
        'latitude',
        'longitude',
        'captured_at',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'latitude'    => 'decimal:8',
        'longitude'   => 'decimal:8',
        'size_bytes'  => 'integer',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroRefeicao::class, 'registro_id');
    }
}
