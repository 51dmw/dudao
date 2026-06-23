<?php

namespace App\Services;

use App\Enums\Grade;
use App\Models\Inspection;

/**
 * 评分引擎：根据巡检明细计算各维度分、总分、等级，并回写网站缓存分。
 */
class ScoringService
{
    // 维度 code → inspections 表对应字段
    private const FIELD_MAP = [
        'product' => 'score_product',
        'content' => 'score_content',
        'ux'      => 'score_ux',
        'ad'      => 'score_ad',
        'exec'    => 'score_exec',
    ];

    public function calculate(Inspection $inspection): Inspection
    {
        $inspection->loadMissing('results.checkItem.dimension');

        // 各维度满分起算，命中异常项(is_normal=0)即扣该项分值
        $scores = [];
        foreach (config('qa.dimensions') as $code => $cfg) {
            $scores[$code] = $cfg['max'];
        }

        foreach ($inspection->results as $result) {
            if (! $result->is_normal) {
                $code = $result->checkItem->dimension->code;
                $scores[$code] -= $result->checkItem->points;
            }
        }

        foreach (self::FIELD_MAP as $code => $field) {
            $inspection->{$field} = max(0, $scores[$code] ?? 0);
        }

        $total = array_sum(array_map(
            fn ($f) => $inspection->{$f},
            self::FIELD_MAP
        )) + (float) $inspection->score_adjust;

        $total = max(0, min(100, $total));

        $inspection->total_score = $total;
        $inspection->grade       = Grade::fromScore($total)->value;
        $inspection->save();

        $this->cacheToWebsite($inspection);

        return $inspection;
    }

    private function cacheToWebsite(Inspection $inspection): void
    {
        $inspection->website->update([
            'current_score'     => $inspection->total_score,
            'current_grade'     => $inspection->grade,
            'last_inspected_at' => now(),
        ]);
    }
}
