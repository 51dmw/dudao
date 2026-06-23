<?php

namespace App\Services;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * 问题状态机：校验合法流转 → 更新状态 → 写 issue_logs 审计 → 维护闭环字段。
 */
class IssueWorkflow
{
    public function transition(Issue $issue, IssueStatus $target, User $operator, ?string $note = null): Issue
    {
        $current = $issue->status;

        if (! $current->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => "不允许从「{$current->label()}」流转到「{$target->label()}」",
            ]);
        }

        // 验收不通过：退回处理中，重复打回计数 +1
        if ($current === IssueStatus::Verifying && $target === IssueStatus::Processing) {
            $issue->increment('repeat_count');
        }

        $issue->status = $target;
        $issue->closed_at = $target === IssueStatus::Closed ? now() : null;
        $issue->save();

        $issue->logs()->create([
            'from_status' => $current->value,
            'to_status'   => $target->value,
            'operator_id' => $operator->id,
            'note'        => $note,
            'created_at'  => now(),
        ]);

        return $issue;
    }

    public function assign(Issue $issue, User $assignee, User $operator): Issue
    {
        $issue->update(['assignee_id' => $assignee->id]);

        $issue->logs()->create([
            'from_status' => $issue->status->value,
            'to_status'   => $issue->status->value,
            'operator_id' => $operator->id,
            'note'        => "指派给 {$assignee->name}",
            'created_at'  => now(),
        ]);

        return $issue;
    }
}
