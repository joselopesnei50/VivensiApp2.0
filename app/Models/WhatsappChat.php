<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class WhatsappChat extends Model
{
    use HasFactory, BelongsToTenant;

    protected $casts = [
        'last_message_at'  => 'datetime',
        'last_inbound_at'  => 'datetime',
        'last_outbound_at' => 'datetime',
        'last_read_at'     => 'datetime',
        'opt_in_at'        => 'datetime',
        'opt_out_at'       => 'datetime',
        'blocked_at'       => 'datetime',
        'labels'           => 'array',
        'assigned_to'      => 'integer',
        'is_bot_active'    => 'boolean',
        'is_group'         => 'boolean',
    ];

    protected $fillable = [
        'tenant_id',
        'wa_id',
        'contact_name',
        'contact_phone',
        'is_group',
        'status',
        'labels',
        'assigned_to',
        'last_message_at',
        'last_inbound_at',
        'last_outbound_at',
        'last_read_at',
        'opt_in_at',
        'opt_in_source',
        'opt_out_at',
        'blocked_at',
        'blocked_reason',
        'is_bot_active',
    ];

    public function messages() {
        return $this->hasMany(WhatsappMessage::class, 'chat_id');
    }

    public function agent() {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Etiquetas atribuídas a este chat (many-to-many via pivot).
     *
     * Nomeado `labelTags()` para não colidir com `$chat->labels` (JSON cast,
     * mantido durante transição para compat com UI antiga). O `WhatsappController::updateLabels`
     * faz dual-write — atualiza pivot E coluna JSON na mesma operação.
     * Coluna JSON será removida em release futura após confirmação em produção.
     */
    public function labelTags()
    {
        return $this->belongsToMany(
            WhatsappLabel::class,
            'whatsapp_chat_label',
            'chat_id',
            'label_id'
        )->withTimestamps();
    }
}
