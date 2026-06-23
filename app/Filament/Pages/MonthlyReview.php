<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Pages\Page;

class MonthlyReview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.monthly-review';
    protected static ?string $navigationGroup = '报表中心';
    protected static ?string $navigationLabel = '月度复盘';
    protected static ?int $navigationSort = 6;

    public function getTitle(): string
    {
        return '月度复盘 · ' . now()->format('Y 年 n 月');
    }

    public function getViewData(): array
    {
        $r = app(ReportService::class);

        return [
            'metrics' => $r->monthlyMetrics(),
            'trend'   => $r->scoreTrend(6),
        ];
    }
}
