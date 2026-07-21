<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Anexo polimorfico — associado a Asset, InventoryItem ou InventoryMovement.
 *
 * Armazenamento: disk 'local' privado, em
 *   storage/app/private/tenants/{tenantId}/attachments/{morphType}/{uuid}.{ext}
 *
 * Acesso: sempre via rota controlada com tenant check (evitar exposicao direta).
 * Nao expor `path` ao publico.
 */
class Attachment extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'attachable_type',
        'attachable_id',
        'original_name',
        'path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'tipo_documento',
        'valid_until',
        'versao',
        'substituido_por_id',
        'alerta_enviado_em',
    ];

    protected $casts = [
        'valid_until'       => 'date',
        'versao'            => 'integer',
        'alerta_enviado_em' => 'datetime',
    ];

    protected $hidden = ['path'];

    public function substituidoPor()
    {
        return $this->belongsTo(Attachment::class, 'substituido_por_id');
    }

    public function versoes()
    {
        return $this->hasMany(Attachment::class, 'substituido_por_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
