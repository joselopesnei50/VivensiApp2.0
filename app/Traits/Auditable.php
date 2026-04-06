<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->recordAudit('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = array_intersect_key($model->getOriginal(), $model->getDirty());
            $new = $model->getDirty();
            $model->recordAudit('updated', $old, $new);
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->getAttributes(), null);
        });
    }

    public function recordAudit($event, $old = null, $new = null)
    {
        AuditLog::create([
            'tenant_id' => $this->tenant_id ?? (auth()->user()->tenant_id ?? 1),
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
        ]);
    }
}
