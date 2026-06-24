<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Device: string implements HasLabel
{
    case All    = 'all';
    case Pc     = 'pc';
    case Mobile = 'mobile';
    case Tablet = 'tablet';

    public function getLabel(): string
    {
        return match ($this) {
            self::All    => '全部',
            self::Pc     => 'PC',
            self::Mobile => '移动端',
            self::Tablet => '平板',
        };
    }
}
