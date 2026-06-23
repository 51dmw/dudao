<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Website;

class WebsitePolicy
{
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, Website $w): bool { return true; }

    public function create(User $u): bool { return $u->canManageWebsites(); }
    public function update(User $u, Website $w): bool { return $u->canManageWebsites(); }
    public function delete(User $u, Website $w): bool { return $u->isAdmin(); }
}
