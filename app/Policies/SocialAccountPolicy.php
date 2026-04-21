<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;

class SocialAccountPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->role === 'super_admin') return true;
        return null;
    }

    public function disconnect(User $user, SocialAccount $account): bool
    {
        return $account->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, SocialAccount $account): bool
    {
        return $account->tenant_id === $user->tenant_id;
    }
}
