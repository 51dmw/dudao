<?php

namespace App\Filament\Widgets;

use App\Models\Website;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class QualityRankWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return '质量排行榜';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Website::query()->orderByDesc('current_score'))
            ->paginated([10])
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('网站'),
                Tables\Columns\TextColumn::make('current_score')->label('评分')
                    ->badge()->color(fn (Website $r) => $r->current_grade?->color() ?? 'gray')->sortable(),
                Tables\Columns\TextColumn::make('current_grade')->label('等级')->badge(),
                Tables\Columns\TextColumn::make('open_issues')->label('待整改')
                    ->state(fn (Website $r) => $r->openIssuesCount())
                    ->badge()->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('pm.name')->label('产品')->placeholder('—'),
                Tables\Columns\TextColumn::make('operator.name')->label('运营')->placeholder('—'),
            ]);
    }
}
