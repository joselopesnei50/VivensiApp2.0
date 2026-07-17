<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait pra models que suportam anexos polimorficos.
 *
 * Aplica em qualquer model que deva ter N arquivos (PDF/JPG/PNG) — atualmente
 * Asset, InventoryItem, InventoryMovement. Uploads gerenciados pelo
 * AttachmentController via rota /attachments/{morphType}/{morphId}.
 */
trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
