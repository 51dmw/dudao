<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, Inspection $i): bool { return true; }

    public function create(User $u): bool { return $u->canInspect(); }

    // 巡检提交后不可改（评分已联动生成问题），仅管理员可删
    public function update(User $u, Inspection $i): bool { return false; }
    public function delete(User $u, Inspection $i): bool { return $u->isAdmin(); }
}
