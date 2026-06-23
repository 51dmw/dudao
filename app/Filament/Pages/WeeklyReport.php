<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Pages\Page;

class WeeklyReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.weekly-report';
    protected static ?string $navigationGroup = '报表中心';
    protected static ?string $navigationLabel = '周报中心';
    protected static ?int $navigationSort = 5;

    public function getTitle(): string
    {
        return '网站质量周报';
    }

    public function getViewData(): array
    {
        $r = app(ReportService::class);

        return [
            'stats'   => $r->weeklyStats(),
            'ranking' => $r->qualityRanking(5),
            'levels'  => $r->issueLevelCounts(),
            'risks'   => $r->riskSites(),
        ];
    }
}
