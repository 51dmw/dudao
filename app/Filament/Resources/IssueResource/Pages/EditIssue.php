<?php

namespace App\Filament\Resources\IssueResource\Pages;

use App\Filament\Resources\IssueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIssue extends EditRecord
{
    protected static string $resource = IssueResource::class;

    /** @var array<string> 暂存截图路径，afterSave 时同步到 issue_attachments */
    protected array $shots = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->shots = $data['screenshots'] ?? [];
        unset($data['screenshots']);

        return $data;
    }

    protected function afterSave(): void
    {
        // 以表单当前截图集合为准，重建附件记录
        $this->record->attachments()->delete();
        foreach ($this->shots as $path) {
            $this->record->attachments()->create(['file_path' => $path, 'created_at' => now()]);
        }
    }
}
