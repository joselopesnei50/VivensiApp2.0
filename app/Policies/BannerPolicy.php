<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->role === 'super_admin') return true;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['manager', 'ngo', 'employee']);
    }

    public function view(User $user, Banner $banner): bool
    {
        return $banner->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['manager', 'ngo', 'employee']);
    }

    public function update(User $user, Banner $banner): bool
    {
        return $banner->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $banner->tenant_id === $user->tenant_id;
    }
}
