<?php

namespace App\Services;

use App\Enums\IssueStatus;
use App\Models\Inspection;
use App\Models\Issue;
use Illuminate\Support\Collection;

/**
 * 巡检 → 问题：把巡检里 is_normal=0 的明细批量转为问题单（待指派），
 * 并带上巡检项的页面类型 / 终端 / 优先级。
 */
class IssueFactory
{
    // 维度 code → 问题分类 type
    private const TYPE_MAP = [
        'product' => 'product', 'content' => 'content', 'ux' => 'ux',
        'ad' => 'ad', 'exec' => 'operation',
    ];

    // 章节 → 页面类型
    private const PAGE_MAP = [
        '首页' => 'home', '分类页' => 'category', '视频详情页' => 'article',
    ];

    // 终端文案 → device 枚举
    private const DEVICE_MAP = [
        '双端' => 'all', 'PC' => 'pc', '移动端' => 'mobile', '平板' => 'tablet',
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
            $level = $item->default_level;          // IssueLevel enum (非空)

            $created->push(Issue::create([
                'website_id'    => $inspection->website_id,
                'inspection_id' => $inspection->id,
                'level'         => $level->value,
                'type'          => self::TYPE_MAP[$item->dimension?->code] ?? 'product',
                'page_type'     => self::PAGE_MAP[$item->section] ?? 'other',
                'device'        => self::DEVICE_MAP[$item->terminal] ?? 'all',
                'title'         => "[{$item->module}] {$item->name}",
                'description'   => $result->remark,
                'reporter_id'   => $inspection->inspector_id,
                'assignee_id'   => null,             // 待指派
                'due_at'        => $level->dueFrom($inspection->created_at),
                'status'        => IssueStatus::Pending->value,
            ]));
        }

        return $created;
    }
}
