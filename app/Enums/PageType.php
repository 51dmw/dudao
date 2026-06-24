<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageType: string implements HasLabel
{
    case Home     = 'home';
    case Category = 'category';
    case Article  = 'article';
    case Topic    = 'topic';
    case Tag      = 'tag';
    case Search   = 'search';
    case Other    = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Home     => '首页',
            self::Category => '分类页',
            self::Article  => '文章页',
            self::Topic    => '专题页',
            self::Tag      => '标签页',
            self::Search   => '搜索页',
            self::Other    => '其他',
        };
    }
}
