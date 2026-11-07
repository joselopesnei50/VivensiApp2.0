<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

trait Auditable
{
    /**
     * Campos que nunca vão em claro para o log de auditoria (LGPD/segredos).
     * Se mudaram, o log registra só que mudaram ("[oculto]").
     * Um model pode acrescentar os seus em `protected array $auditExclude`.
     */
    protected static array $auditSensitive = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'cpf', 'cpf_bidx', 'nis', 'nis_bidx', 'rg', 'birth_date',
        'public_token', 'public_token_bidx', 'secret', 'api_key', 'api_token', 'access_token', 'token',
    ];

    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->recordAudit('created', null, $model->auditSafe($model->getAttributes()));
        });

        static::updated(function ($model) {
            $old = array_intersect_key($model->getOriginal(), $model->getDirty());
            $new = $model->getDirty();
            $model->recordAudit('updated', $model->auditSafe($old), $model->auditSafe($new));
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->auditSafe($model->getAttributes()), null);
        });
    }

    public function auditSafe(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }
        $hidden = array_merge(static::$auditSensitive, property_exists($this, 'auditExclude') ? (array) $this->auditExclude : []);
        foreach ($hidden as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = ($values[$key] === null || $values[$key] === '') ? $values[$key] : '[oculto]';
            }
        }
        return $values;
    }

    public function recordAudit(string $event, $old = null, $new = null): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $this->tenant_id ?? (auth()->user()?->tenant_id ?? 1),
                'user_id'        => auth()->id(),
                'event'          => $event,
                'auditable_type' => get_class($this),
                'auditable_id'   => $this->id,
                'old_values'     => $old,
                'new_values'     => $new,
                'ip_address'     => Request::ip(),
                'user_agent'     => Request::userAgent(),
                'url'            => Request::fullUrl(),
                'session_id'     => self::safeSessionId(),
                'device_type'    => self::detectDeviceType(Request::userAgent() ?? ''),
                'browser'        => self::detectBrowser(Request::userAgent() ?? ''),
                'platform'       => self::detectPlatform(Request::userAgent() ?? ''),
            ]);
        } catch (\Throwable $e) {
            // Auditoria não deve bloquear a operação principal
            \Log::warning('Audit log falhou: ' . $e->getMessage(), [
                'event'  => $event,
                'model'  => get_class($this),
                'id'     => $this->id ?? null,
            ]);
        }
    }

    private static function safeSessionId(): ?string
    {
        try {
            return Session::isStarted() ? Session::getId() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function detectDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua))        return 'tablet';
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $ua)) return 'mobile';
        return 'desktop';
    }

    private static function detectBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))          return 'Edge';
        if (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) return 'Opera';
        if (str_contains($ua, 'Chrome'))       return 'Chrome';
        if (str_contains($ua, 'Firefox'))      return 'Firefox';
        if (str_contains($ua, 'Safari'))       return 'Safari';
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) return 'IE';
        return 'Other';
    }

    private static function detectPlatform(string $ua): string
    {
        if (str_contains($ua, 'Windows NT')) return 'Windows';
        if (str_contains($ua, 'Mac OS X'))   return 'macOS';
        if (str_contains($ua, 'Android'))    return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Linux'))      return 'Linux';
        return 'Other';
    }
}
