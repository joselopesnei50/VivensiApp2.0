<?php

namespace App\Observers;

use App\Jobs\RecalcularConformidadeJob;
use App\Models\Attachment;

class AttachmentConformidadeObserver
{
    public function created(Attachment $attachment): void
    {
        $this->dispatch($attachment);
    }

    public function updated(Attachment $attachment): void
    {
        if ($attachment->wasChanged(['tipo_documento', 'valid_until'])) {
            $this->dispatch($attachment);
        }
    }

    public function deleted(Attachment $attachment): void
    {
        if ($attachment->tipo_documento) {
            $this->dispatch($attachment);
        }
    }

    private function dispatch(Attachment $attachment): void
    {
        if ($attachment->tenant_id && $attachment->tipo_documento) {
            RecalcularConformidadeJob::dispatch($attachment->tenant_id);
        }
    }
}
