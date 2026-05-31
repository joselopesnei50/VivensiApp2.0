<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesLeadActivity extends Model
{
    public $timestamps = false;

    protected $fillable = ['lead_id', 'user_id', 'type', 'content'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(SalesLead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'stage_changed' => 'Estágio alterado',
            'note_added'    => 'Anotação',
            'converted'     => 'Convertido em cliente',
            default         => 'Criado',
        };
    }
}
