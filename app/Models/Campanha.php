<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campanha extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'titulo', 'mensagem', 'status',
        'agendada_para', 'total_contatos', 'total_enviados',
        'total_falhas', 'intervalo_segundos',
    ];

    protected $casts = [
        'agendada_para' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function progresso(): int
    {
        if ($this->total_contatos === 0) return 0;
        return (int) round(($this->total_enviados + $this->total_falhas) / $this->total_contatos * 100);
    }

    public function estaProcessando(): bool
    {
        return $this->status === 'processando';
    }
}
