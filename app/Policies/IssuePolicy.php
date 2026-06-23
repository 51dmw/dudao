<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, Issue $i): bool { return true; }

    // 督导线建单/改单；处理人不直接编辑字段，只通过流转动作
    public function create(User $u): bool { return $u->canSupervise(); }
    public function update(User $u, Issue $i): bool { return $u->canSupervise(); }
    public function delete(User $u, Issue $i): bool { return $u->isAdmin(); }
}
