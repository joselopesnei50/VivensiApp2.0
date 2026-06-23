<?php

namespace App\Policies;

use App\Models\RegistroRefeicao;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistroRefeicaoPolicy
{
    use HandlesAuthorization;

    public function view(User $user, RegistroRefeicao $registro): bool
    {
        if ($user->tenant_id !== $registro->tenant_id) {
            return false;
        }
        if ($user->role === 'coordenador_cozinha') {
            return in_array($registro->cozinha_id, $user->cozinhasAtivasIds(), true);
        }
        return in_array($user->role, ['ngo', 'manager'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['coordenador_cozinha', 'ngo', 'manager'], true);
    }

    public function update(User $user, RegistroRefeicao $registro): bool
    {
        if ($user->tenant_id !== $registro->tenant_id) {
            return false;
        }
        if ($user->role === 'coordenador_cozinha') {
            return in_array($registro->cozinha_id, $user->cozinhasAtivasIds(), true);
        }
        return in_array($user->role, ['ngo', 'manager'], true);
    }

    /**
     * Fechamento de período é responsabilidade de NGO/super_admin —
     * separação de papéis (coordenador NUNCA fecha).
     */
    public function fecharPeriodo(User $user, RegistroRefeicao $registro): bool
    {
        return $user->tenant_id === $registro->tenant_id
            && in_array($user->role, ['ngo'], true);
    }

    /**
     * Aprovação de estorno: NGO/super_admin. Em modalidade direta, o service
     * já auto-aprova; aqui controlamos só o caminho indireta.
     */
    public function aprovarEstorno(User $user, RegistroRefeicao $registro): bool
    {
        return $user->tenant_id === $registro->tenant_id
            && in_array($user->role, ['ngo'], true);
    }
}
