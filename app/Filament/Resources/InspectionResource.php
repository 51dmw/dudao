<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InspectionResource\Pages;
use App\Models\CheckItem;
use App\Models\Inspection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = '质量管理';
    protected static ?string $navigationLabel = '巡检表单';
    protected static ?string $modelLabel = '巡检';
    protected static ?string $pluralModelLabel = '巡检';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('巡检信息')->columns(3)->schema([
                Forms\Components\Select::make('website_id')->label('网站')
                    ->relationship('website', 'name')->required()->searchable(),
                Forms\Components\Select::make('inspector_id')->label('巡检人')
                    ->relationship('inspector', 'name')->required()
                    ->default(fn () => auth()->id()),
                Forms\Components\DatePicker::make('inspect_date')->label('巡检日期')
                    ->required()->default(now()),
            ]),

            // 按维度分组渲染检查项开关：开=正常(默认)，关=异常(扣分并生成问题)
            ...static::checklistSections(),

            Forms\Components\Textarea::make('remark')->label('巡检备注')->rows(2)
                ->visible(fn (string $operation) => $operation === 'create'),
        ]);
    }

    // 按 章节(页面类型) → 模块 两级渲染巡检项开关；开=正常(默认)，关=异常(扣分并生成问题)
    protected static function checklistSections(): array
    {
        return CheckItem::where('is_active', true)->orderBy('sort')->get()
            ->groupBy('section')
            ->map(function ($itemsInSection, $section) {
                $moduleFieldsets = $itemsInSection->groupBy('module')
                    ->map(function ($items, $module) {
                        $toggles = $items->map(fn (CheckItem $item) =>
                            Forms\Components\Toggle::make("items.{$item->id}")
                                ->label($item->name . ($item->default_level ? "　[{$item->default_level->value}]" : ''))
                                ->inline(false)->default(true)
                                ->onColor('success')->offColor('danger')
                        )->all();

                        return Forms\Components\Fieldset::make($module)->columns(2)->schema($toggles);
                    })->values()->all();

                return Forms\Components\Section::make($section)
                    ->description($itemsInSection->count() . ' 项')
                    ->collapsed()->collapsible()
                    ->schema($moduleFieldsets);
            })->values()->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('inspect_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('website.name')->label('网站')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('inspector.name')->label('巡检人'),
                Tables\Columns\TextColumn::make('inspect_date')->label('日期')->date('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('total_score')->label('总分')->sortable()
                    ->badge()->color(fn (Inspection $r) => $r->grade?->color() ?? 'gray'),
                Tables\Columns\TextColumn::make('grade')->label('等级')->badge()->sortable(),
                Tables\Columns\TextColumn::make('issues_count')->label('生成问题')->counts('issues')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('提交时间')->dateTime('m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('website_id')->label('网站')->relationship('website', 'name'),
                Tables\Filters\SelectFilter::make('grade')->label('等级')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E']),
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
            'index'  => Pages\ListInspections::route('/'),
            'create' => Pages\CreateInspection::route('/create'),
            'edit'   => Pages\EditInspection::route('/{record}/edit'),
        ];
    }
}
