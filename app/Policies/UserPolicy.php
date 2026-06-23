<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // 人员管理仅管理员
    public function viewAny(User $u): bool { return $u->isAdmin(); }
    public function view(User $u, User $m): bool { return $u->isAdmin(); }
    public function create(User $u): bool { return $u->isAdmin(); }
    public function update(User $u, User $m): bool { return $u->isAdmin(); }
    public function delete(User $u, User $m): bool { return $u->isAdmin() && $u->id !== $m->id; }
}
