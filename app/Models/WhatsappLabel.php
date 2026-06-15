<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLabel extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'whatsapp_labels';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'color',
        'background',
        'created_by',
    ];

    /**
     * Chats associados a esta etiqueta (many-to-many via pivot).
     */
    public function chats()
    {
        return $this->belongsToMany(
            WhatsappChat::class,
            'whatsapp_chat_label',
            'label_id',
            'chat_id'
        )->withTimestamps();
    }

    /**
     * Usuário que criou a etiqueta (auditoria).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
