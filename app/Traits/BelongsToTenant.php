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
            // No console, ignoramos o filtro para evitar travamentos em comandos artisan
            if (app()->runningInConsole()) {
                return;
            }

            if (Auth::check()) {
                $user = Auth::user();
                if ($user && $user->role !== 'super_admin') {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
                }
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
