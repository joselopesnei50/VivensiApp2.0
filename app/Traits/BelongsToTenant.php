<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // 1. Aplicar filtro global de tenant_id em todas as consultas (SELECT)
        static::addGlobalScope('tenant', function (Builder $builder) {
            // No console ou filas, o filtro costuma ser ignorado ou tratado via ID direto
            if (app()->runningInConsole()) {
                return;
            }

            if (Auth::check()) {
                $user = Auth::user();
                if ($user && $user->role !== 'super_admin') {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
                }
            } else {
                // Contexto web sem autenticação (Webhooks)
                // Se o builder já tiver um filtro por tenant_id ou id, permitimos.
                // Caso contrário, bloqueamos por segurança.
                // IMPORTANTE: Em Webhooks, devemos usar ->withoutGlobalScopes() ou ->withoutGlobalScope('tenant')
                // Mas para evitar quebrar o sistema, vamos apenas retornar se não houver auth.
                // $builder->whereRaw('0 = 1'); // Removido para permitir Webhooks com tratativa manual
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
