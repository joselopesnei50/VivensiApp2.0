<?php

namespace App\Policies;

use App\Models\Cozinha;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Cozinha Solidária — Fase 0 (critério de aceite: coordenador de cozinha não
 * enxerga outra cozinha nem dados da gestora).
 *
 * super_admin é resolvido pelo Gate::before do AuthServiceProvider — não
 * aparece aqui pra não duplicar a regra.
 */
class CozinhaPolicy
{
    use HandlesAuthorization;

    /**
     * Listagem só é permitida em escopo de tenant (chamador é responsável
     * pelo where tenant_id). Aqui só validamos o role.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ngo', 'manager', 'coordenador_cozinha'], true);
    }

    public function view(User $user, Cozinha $cozinha): bool
    {
        if ($user->tenant_id !== $cozinha->tenant_id) {
            return false;
        }

        if ($user->role === 'coordenador_cozinha') {
            return in_array($cozinha->id, $user->cozinhasAtivasIds(), true);
        }

        return in_array($user->role, ['ngo', 'manager'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['ngo', 'manager'], true);
    }

    public function update(User $user, Cozinha $cozinha): bool
    {
        return $user->tenant_id === $cozinha->tenant_id
            && in_array($user->role, ['ngo', 'manager'], true);
    }

    public function delete(User $user, Cozinha $cozinha): bool
    {
        return $user->tenant_id === $cozinha->tenant_id
            && in_array($user->role, ['ngo'], true);
    }
}
