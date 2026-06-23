<?php

namespace App\Filament\Resources\InspectionResource\Pages;

use App\Filament\Resources\InspectionResource;
use App\Models\CheckItem;
use App\Models\Inspection;
use App\Services\IssueFactory;
use App\Services\ScoringService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateInspection extends CreateRecord
{
    protected static string $resource = InspectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * 巡检提交全流程：建巡检 → 写明细 → 算分 → 异常项生成问题单。
     */
    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];   // [check_item_id => bool(是否正常)]

        return DB::transaction(function () use ($data, $items) {
            $inspection = Inspection::create([
                'website_id'   => $data['website_id'],
                'inspector_id' => $data['inspector_id'],
                'inspect_date' => $data['inspect_date'],
                'remark'       => $data['remark'] ?? null,
                'status'       => 'submitted',
            ]);

            foreach (CheckItem::all() as $item) {
                $inspection->results()->create([
                    'check_item_id' => $item->id,
                    'is_normal'     => (bool) ($items[$item->id] ?? true),
                ]);
            }

            app(ScoringService::class)->calculate($inspection);
            $issues = app(IssueFactory::class)->generateFromInspection($inspection);

            Notification::make()
                ->title("巡检已提交：{$inspection->total_score} 分（{$inspection->grade?->value} 级）")
                ->body($issues->isEmpty()
                    ? '无异常项，满分通过'
                    : "已自动生成 {$issues->count()} 个问题单（待指派）")
                ->success()->send();

            return $inspection;
        });
    }
}
