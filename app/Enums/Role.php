<?php

namespace App\Enums;

enum Role: string
{
    case Admin      = 'admin';
    case Supervisor = 'supervisor';  // 督导/质检
    case Pm         = 'pm';          // 产品经理
    case Operator   = 'operator';    // 运营
    case Seo        = 'seo';
    case Manager    = 'manager';     // 主管

    public function label(): string
    {
        return match ($this) {
            self::Admin      => '管理员',
            self::Supervisor => '督导/质检',
            self::Pm         => '产品经理',
            self::Operator   => '运营',
            self::Seo        => 'SEO',
            self::Manager    => '主管',
        };
    }
}
