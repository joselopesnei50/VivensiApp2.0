<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    protected static function bootBelongsToTenant()
    {
        // 1. Aplicar filtro global de tenant_id em todas as consultas (SELECT)
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->runningInConsole()) {
                // Scope ignorado intencionalmente em console/queue — commands devem filtrar
                // por tenant_id explicitamente. Log em debug para detectar usos acidentais.
                \Illuminate\Support\Facades\Log::debug(
                    'BelongsToTenant scope bypassed in console for ' . get_class($builder->getModel()),
                    ['trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4)]
                );
                return;
            }

            if (Auth::check()) {
                $user = Auth::user();
                if ($user && !$user->isSuperAdmin()) {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
                }
            } else {
                // FAIL-CLOSED (Fase 2 do hardening): contexto web sem autenticação
                // não vê NENHUM registro tenant-scoped por padrão. Rotas públicas
                // legítimas (transparência, rifas, SIC, webhooks Evolution/OpenPix)
                // usam ->withoutGlobalScopes() explicitamente e mantêm filtro manual
                // por tenant_id/slug/token — o bypass fica visível e auditável.
                $builder->whereRaw('1 = 0');
            }
        });

        // 2. Definir automaticamente o tenant_id ao criar novos registros (INSERT)
        static::creating(function (Model $model) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user && !$model->tenant_id && $user->tenant_id) {
                    $model->tenant_id = $user->tenant_id;
                }
            }
        });
    }
}
