<?php

namespace App\Services;

use App\Enums\IssueStatus;
use App\Models\Inspection;
use App\Models\Issue;
use Illuminate\Support\Collection;

/**
 * 巡检 → 问题：把巡检里 is_normal=0 的明细批量转为问题单（待指派）。
 */
class IssueFactory
{
    // 维度 code → 问题分类 type
    private const TYPE_MAP = [
        'product' => 'product',
        'content' => 'content',
        'ux'      => 'ux',
        'ad'      => 'ad',
        'exec'    => 'operation',
    ];

    public function generateFromInspection(Inspection $inspection): Collection
    {
        $inspection->loadMissing('results.checkItem.dimension', 'website');

        $created = collect();

        foreach ($inspection->results as $result) {
            if ($result->is_normal) {
                continue;
            }

            $item  = $result->checkItem;
            $level = $item->default_level;          // IssueLevel enum

            $created->push(Issue::create([
                'code'          => $this->nextCode(),
                'website_id'    => $inspection->website_id,
                'inspection_id' => $inspection->id,
                'level'         => $level->value,
                'type'          => self::TYPE_MAP[$item->dimension->code] ?? 'product',
                'title'         => "[{$item->name}] 检查发现异常",
                'description'   => $result->remark,
                'reporter_id'   => $inspection->inspector_id,
                'assignee_id'   => null,             // 待指派
                'due_at'        => $level->dueFrom($inspection->created_at),
                'status'        => IssueStatus::Pending->value,
            ]));
        }

        return $created;
    }

    // 生成 #001 形式的编号（演示用；生产可换成按年月+序列）
    private function nextCode(): string
    {
        $next = (int) (Issue::max('id') ?? 0) + 1;
        return '#' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
