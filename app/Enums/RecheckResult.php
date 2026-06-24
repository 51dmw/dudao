<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RecheckResult: string implements HasLabel, HasColor
{
    case Pending = 'pending';  // 未复检
    case Pass    = 'pass';     // 通过
    case Fail    = 'fail';     // 不通过

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '未复检',
            self::Pass    => '通过',
            self::Fail    => '不通过',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Pass    => 'success',
            self::Fail    => 'danger',
        };
    }
}
