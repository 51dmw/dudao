<?php

namespace App\Enums;

enum Grade: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    // 总分 → 等级
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 90 => self::A,
            $score >= 80 => self::B,
            $score >= 70 => self::C,
            $score >= 60 => self::D,
            default      => self::E,
        };
    }

    // 驾驶舱/表格用的语义色
    public function color(): string
    {
        return match ($this) {
            self::A, self::B => 'success',
            self::C          => 'warning',
            self::D, self::E => 'danger',
        };
    }
}
