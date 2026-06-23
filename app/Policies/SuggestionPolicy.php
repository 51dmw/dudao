<?php

namespace App\Policies;

use App\Models\Suggestion;
use App\Models\User;

class SuggestionPolicy
{
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, Suggestion $s): bool { return true; }

    // 任何成员都可提交优化建议
    public function create(User $u): bool { return true; }

    // 提交人本人或督导线可改
    public function update(User $u, Suggestion $s): bool
    {
        return $u->canSupervise() || $s->created_by === $u->id;
    }

    public function delete(User $u, Suggestion $s): bool { return $u->isAdmin(); }
}
