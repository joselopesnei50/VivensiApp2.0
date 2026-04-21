<?php

namespace App\Policies;

use App\Models\ScheduledPost;
use App\Models\User;

class ScheduledPostPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->role === 'super_admin') return true;
        return null;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['manager', 'ngo', 'employee']);
    }

    public function delete(User $user, ScheduledPost $post): bool
    {
        return $post->tenant_id === $user->tenant_id;
    }
}
