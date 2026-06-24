<?php

namespace App\Services;

use App\Enums\Grade;
use App\Models\Inspection;

/**
 * 评分引擎（优先级扣分制）：满分 100，失败项(is_normal=0)按其优先级扣分，
 * 再叠加整改加减分，得出总分与等级，并回写网站缓存分。
 */
class ScoringService
{
    public function calculate(Inspection $inspection): Inspection
    {
        $inspection->loadMissing('results.checkItem');

        $deductions = config('qa.priority_deductions');
        $total = 100.0;

        foreach ($inspection->results as $result) {
            if (! $result->is_normal) {
                $level = $result->checkItem->default_level?->value ?? 'P2';
                $total -= (float) ($deductions[$level] ?? $deductions['P2']);
            }
        }

        $total = max(0, min(100, $total + (float) $inspection->score_adjust));

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
