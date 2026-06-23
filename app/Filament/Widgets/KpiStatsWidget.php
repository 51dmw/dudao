<?php

namespace App\Filament\Widgets;

use App\Models\Issue;
use App\Models\Website;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $avg  = round((float) Website::avg('current_score'), 1);
        $open = Issue::open()->count();
        $p0   = Issue::open()->where('level', 'P0')->count();
        $risk = Website::where('current_score', '<', 70)->count();

        return [
            Stat::make('网站总数', Website::count())
                ->description('纳入巡检的站点')->color('primary'),
            Stat::make('平均质量分', $avg)
                ->description($avg >= 80 ? '整体健康' : '需关注')
                ->color($avg >= 80 ? 'success' : 'warning'),
            Stat::make('待整改问题', $open)
                ->description("高风险站点 {$risk} 个")->color($open > 0 ? 'warning' : 'success'),
            Stat::make('P0 问题', $p0)
                ->description($p0 > 0 ? '需立即处理' : '无')->color($p0 > 0 ? 'danger' : 'success'),
        ];
    }
}
