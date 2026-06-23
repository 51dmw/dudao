<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Issue;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Collection;

/**
 * 报表聚合：周报 / 月度复盘 / 人员绩效 的数据来源，全部基于现有表的查询。
 */
class ReportService
{
    /** 质量排行榜 */
    public function qualityRanking(int $limit = 10): Collection
    {
        return Website::orderByDesc('current_score')->limit($limit)->get();
    }

    /** 高风险网站（<70） */
    public function riskSites(): Collection
    {
        return Website::where('current_score', '<', 70)->orderBy('current_score')->get();
    }

    /** 全部问题按等级分布 */
    public function issueLevelCounts(): array
    {
        $base = ['P0' => 0, 'P1' => 0, 'P2' => 0, 'P3' => 0];
        return array_merge($base, Issue::selectRaw('level, count(*) c')
            ->groupBy('level')->pluck('c', 'level')->toArray());
    }

    /** 周报核心数字（近 7 天） */
    public function weeklyStats(): array
    {
        $since = now()->subDays(7);
        return [
            'new'    => Issue::where('created_at', '>=', $since)->count(),
            'closed' => Issue::where('status', 'closed')->where('closed_at', '>=', $since)->count(),
            'open'   => Issue::open()->count(),
            'p0'     => Issue::open()->where('level', 'P0')->count(),
        ];
    }

    /** 月度复盘指标 */
    public function monthlyMetrics(): array
    {
        $total   = Issue::count();
        $closed  = Issue::where('status', 'closed')->count();
        $repeat  = Issue::where('repeat_count', '>', 0)->count();
        $sites   = Website::count();
        $inspected = Website::whereNotNull('last_inspected_at')->count();

        return [
            'avg_score'   => round((float) Website::avg('current_score'), 1),
            'close_rate'  => $total ? round($closed / $total * 100) : 0,
            'repeat_rate' => $total ? round($repeat / $total * 100) : 0,
            'coverage'    => $sites ? round($inspected / $sites * 100) : 0,
        ];
    }

    /** 月均质量分趋势（按巡检月份聚合，DB 无关，PHP 计算） */
    public function scoreTrend(int $months = 6): array
    {
        $rows = Inspection::get(['inspect_date', 'total_score'])
            ->groupBy(fn ($i) => $i->inspect_date->format('Y-m'))
            ->map(fn ($g) => round($g->avg('total_score'), 1))
            ->sortKeys();

        // 不足时用当前网站均分兜底，保证页面有内容
        if ($rows->isEmpty()) {
            return [['label' => now()->format('Y-m'), 'value' => round((float) Website::avg('current_score'), 1)]];
        }

        return $rows->take(-$months)
            ->map(fn ($v, $k) => ['label' => $k, 'value' => $v])
            ->values()->toArray();
    }

    /** 督导巡检量排行 */
    public function inspectorWorkload(): Collection
    {
        return User::query()
            ->withCount('inspections')
            ->orderByDesc('inspections_count')
            ->get(['id', 'name'])
            ->filter(fn ($u) => $u->inspections_count > 0)
            ->values();
    }

    /** 整改效率：每个责任人的关闭率 + 平均处理时长（小时） */
    public function fixEfficiency(): Collection
    {
        return User::query()
            ->withCount([
                'assignedIssues as total',
                'assignedIssues as closed' => fn ($q) => $q->where('status', 'closed'),
            ])
            ->get(['id', 'name'])
            ->filter(fn ($u) => $u->total > 0)
            ->map(function ($u) {
                $avgHours = Issue::where('assignee_id', $u->id)
                    ->whereNotNull('closed_at')
                    ->get(['created_at', 'closed_at'])
                    ->avg(fn ($i) => $i->created_at->diffInHours($i->closed_at));

                return (object) [
                    'name'       => $u->name,
                    'total'      => $u->total,
                    'close_rate' => $u->total ? round($u->closed / $u->total * 100) : 0,
                    'avg_hours'  => $avgHours ? round($avgHours, 1) : null,
                ];
            })
            ->sortByDesc('close_rate')
            ->values();
    }
}
