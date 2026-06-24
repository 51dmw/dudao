<?php

namespace App\Filament\Resources\IssueResource\Pages;

use App\Filament\Resources\IssueResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $shots = $data['screenshots'] ?? [];
        unset($data['screenshots']);

        $issue = static::getModel()::create($data);

        foreach ($shots as $path) {
            $issue->attachments()->create(['file_path' => $path, 'created_at' => now()]);
        }

        return $issue;
    }
}
