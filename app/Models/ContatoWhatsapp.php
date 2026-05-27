<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContatoWhatsapp extends Model
{
    protected $table = 'contatos_whatsapp';

    protected $fillable = [
        'tenant_id', 'telefone', 'nome',
        'opt_in', 'opt_in_at', 'opt_in_origem',
        'opt_out', 'opt_out_at',
    ];

    protected $casts = [
        'opt_in'     => 'boolean',
        'opt_out'    => 'boolean',
        'opt_in_at'  => 'datetime',
        'opt_out_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('opt_in', true)->where('opt_out', false);
    }

    public function registrarOptIn(string $origem = 'bot'): void
    {
        $this->update([
            'opt_in'        => true,
            'opt_in_at'     => now(),
            'opt_in_origem' => $origem,
            'opt_out'       => false,
            'opt_out_at'    => null,
        ]);
    }

    public function registrarOptOut(): void
    {
        $this->update([
            'opt_out'    => true,
            'opt_out_at' => now(),
        ]);
    }

    public function podeReceberMensagem(): bool
    {
        return $this->opt_in && !$this->opt_out;
    }
}
