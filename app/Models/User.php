<?php

namespace App\Models;

use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'role'      => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    public function assignedIssues(): HasMany
    {
        return $this->hasMany(Issue::class, 'assignee_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /* ---------- 角色能力（权限判断的单一来源） ---------- */

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** 督导线：建巡检、建问题、指派、验收关闭 —— 管理员/主管/督导 */
    public function canSupervise(): bool
    {
        return in_array($this->role, [Role::Admin, Role::Manager, Role::Supervisor], true);
    }

    /** 验收/关闭问题（与 canSupervise 同口径，单列以便日后区分） */
    public function canAudit(): bool
    {
        return $this->canSupervise();
    }

    /** 创建巡检 —— 管理员/督导 */
    public function canInspect(): bool
    {
        return in_array($this->role, [Role::Admin, Role::Supervisor], true);
    }

    /** 维护网站档案 —— 管理员/主管 */
    public function canManageWebsites(): bool
    {
        return in_array($this->role, [Role::Admin, Role::Manager], true);
    }

    /** 处理问题（推进到待验收）—— 管理员/产品/运营 */
    public function canHandleIssues(): bool
    {
        return in_array($this->role, [Role::Admin, Role::Pm, Role::Operator], true);
    }
}
