<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteResource\Pages;
use App\Models\Website;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebsiteResource extends Resource
{
    protected static ?string $model = Website::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = '基础档案';
    protected static ?string $navigationLabel = '网站档案';
    protected static ?string $modelLabel = '网站';
    protected static ?string $pluralModelLabel = '网站';
    protected static ?int $navigationSort = 1;

    protected static array $statusOptions = [
        'normal' => '正常', 'warning' => '预警', 'offline' => '下线',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('基础信息')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('网站名称')->required(),
                Forms\Components\TextInput::make('domain')->label('域名')->required(),
                Forms\Components\Select::make('status')->label('状态')
                    ->options(static::$statusOptions)->default('normal')->required(),
                Forms\Components\DatePicker::make('online_at')->label('上线时间'),
            ]),
            Forms\Components\Section::make('负责人')->columns(2)->schema([
                Forms\Components\Select::make('pm_id')->label('产品负责人')->relationship('pm', 'name')->searchable(),
                Forms\Components\Select::make('operator_id')->label('运营负责人')->relationship('operator', 'name')->searchable(),
                Forms\Components\Select::make('seo_id')->label('SEO负责人')->relationship('seo', 'name')->searchable(),
                Forms\Components\Select::make('manager_id')->label('项目负责人')->relationship('manager', 'name')->searchable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('current_score', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('网站')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('domain')->label('域名')->searchable()->color('gray'),
                Tables\Columns\TextColumn::make('pm.name')->label('产品')->placeholder('—'),
                Tables\Columns\TextColumn::make('operator.name')->label('运营')->placeholder('—'),
                Tables\Columns\TextColumn::make('current_score')->label('评分')->sortable()
                    ->badge()->color(fn (Website $r) => $r->current_grade?->color() ?? 'gray'),
                Tables\Columns\TextColumn::make('current_grade')->label('等级')->badge()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge()
                    ->formatStateUsing(fn ($state) => static::$statusOptions[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'normal' => 'success', 'warning' => 'warning', 'offline' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_inspected_at')->label('最近巡检')->dateTime('m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('状态')->options(static::$statusOptions),
                Tables\Filters\SelectFilter::make('current_grade')->label('等级')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('编辑'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWebsites::route('/'),
            'create' => Pages\CreateWebsite::route('/create'),
            'edit'   => Pages\EditWebsite::route('/{record}/edit'),
        ];
    }
}
