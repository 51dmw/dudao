<?php

namespace App\Filament\Resources;

use App\Enums\IssueLevel;
use App\Enums\IssueStatus;
use App\Filament\Resources\IssueResource\Pages;
use App\Models\Issue;
use App\Models\User;
use App\Services\IssueWorkflow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = '质量管理';
    protected static ?string $navigationLabel = '问题中心';
    protected static ?string $modelLabel = '问题';
    protected static ?string $pluralModelLabel = '问题';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::open()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::open()->where('level', 'P0')->exists() ? 'danger' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('website_id')
                ->label('网站')->relationship('website', 'name')->required()->searchable(),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('level')->label('等级')
                    ->options(IssueLevel::class)->required(),
                Forms\Components\Select::make('type')->label('分类')
                    ->options([
                        'product' => '产品', 'operation' => '运营', 'ad' => '广告',
                        'content' => '内容', 'seo' => 'SEO', 'ux' => '体验',
                    ])->required(),
                Forms\Components\Select::make('status')->label('状态')
                    ->options(IssueStatus::class)->default('pending')->required(),
            ]),
            Forms\Components\TextInput::make('title')->label('问题标题')->required()->maxLength(120),
            Forms\Components\Textarea::make('description')->label('问题描述')->rows(3),
            Forms\Components\TextInput::make('page_url')->label('问题页面链接')->url(),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('reporter_id')->label('提交人')
                    ->relationship('reporter', 'name')->required(),
                Forms\Components\Select::make('assignee_id')->label('责任人')
                    ->relationship('assignee', 'name')->placeholder('待指派'),
                Forms\Components\DateTimePicker::make('due_at')->label('截止时间'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('编号')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('level')->label('等级')->badge()->sortable(),
                Tables\Columns\TextColumn::make('website.name')->label('网站')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('问题')->wrap()->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('assignee.name')->label('责任人')
                    ->badge()
                    ->color(fn ($state) => $state ? 'gray' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ?: '⚠ 待指派'),
                Tables\Columns\TextColumn::make('due_at')->label('截止')->dateTime('m-d H:i')
                    ->color(fn (Issue $r) => $r->isOverdue() ? 'danger' : null)->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')->label('等级')->options(IssueLevel::class),
                Tables\Filters\SelectFilter::make('status')->label('状态')->options(IssueStatus::class),
                Tables\Filters\SelectFilter::make('website_id')->label('网站')->relationship('website', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('advance')
                    ->label('推进状态')->icon('heroicon-m-arrow-right-circle')->button()->color('info')
                    ->visible(fn (Issue $r) => ! empty($r->status->allowedNext()))
                    ->form(fn (Issue $r) => [
                        Forms\Components\Select::make('to')->label('流转到')->required()
                            ->options(collect($r->status->allowedNext())
                                ->mapWithKeys(fn (IssueStatus $s) => [$s->value => $s->label()])->all()),
                        Forms\Components\Textarea::make('note')->label('备注')->rows(2),
                    ])
                    ->action(function (Issue $r, array $data) {
                        app(IssueWorkflow::class)->transition(
                            $r, IssueStatus::from($data['to']), auth()->user(), $data['note'] ?? null
                        );
                        Notification::make()->title('状态已更新')->success()->send();
                    }),
                Tables\Actions\Action::make('assign')
                    ->label('指派')->icon('heroicon-m-user-plus')->button()->color('gray')
                    ->form([
                        Forms\Components\Select::make('assignee_id')->label('责任人')->required()
                            ->options(User::active()->pluck('name', 'id')),
                    ])
                    ->action(function (Issue $r, array $data) {
                        app(IssueWorkflow::class)->assign($r, User::find($data['assignee_id']), auth()->user());
                        Notification::make()->title('已指派')->success()->send();
                    }),
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
            'index'  => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit'   => Pages\EditIssue::route('/{record}/edit'),
        ];
    }
}
