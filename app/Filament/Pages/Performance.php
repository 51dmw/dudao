<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Pages\Page;

class Performance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static string $view = 'filament.pages.performance';
    protected static ?string $navigationGroup = '报表中心';
    protected static ?string $navigationLabel = '人员绩效';
    protected static ?int $navigationSort = 7;

    public function getTitle(): string
    {
        return '人员绩效排行 · ' . now()->format('n 月');
    }

    public function getViewData(): array
    {
        $r = app(ReportService::class);

        return [
            'workload'   => $r->inspectorWorkload(),
            'efficiency' => $r->fixEfficiency(),
        ];
    }
}
