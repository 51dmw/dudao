<?php

namespace App\Enums;

use Carbon\Carbon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IssueLevel: string implements HasLabel, HasColor
{
    case P0 = 'P0';
    case P1 = 'P1';
    case P2 = 'P2';
    case P3 = 'P3';

    public function label(): string
    {
        return match ($this) {
            self::P0 => 'P0 · 立即处理',
            self::P1 => 'P1 · 24小时内',
            self::P2 => 'P2 · 3天内',
            self::P3 => 'P3 · 优化建议',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::P0, self::P1 => 'danger',
            self::P2           => 'warning',
            self::P3           => 'info',
        };
    }

    // 处理时限（小时）；null = 不限
    public function slaHours(): ?int
    {
        return match ($this) {
            self::P0 => 0,
            self::P1 => 24,
            self::P2 => 72,
            self::P3 => null,
        };
    }

    // 根据等级算截止时间
    public function dueFrom(?Carbon $base = null): ?Carbon
    {
        $hours = $this->slaHours();
        if ($hours === null) {
            return null;
        }
        return ($base ?? now())->copy()->addHours($hours);
    }
}
