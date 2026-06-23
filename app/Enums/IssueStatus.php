<?php

namespace App\Enums;

enum IssueStatus: string
{
    case Pending    = 'pending';     // 待处理
    case Processing = 'processing';  // 处理中
    case Verifying  = 'verifying';   // 待验收
    case Closed     = 'closed';      // 已关闭
    case Evaluating = 'evaluating';  // 待评估(优化建议线)

    public function label(): string
    {
        return match ($this) {
            self::Pending    => '待处理',
            self::Processing => '处理中',
            self::Verifying  => '待验收',
            self::Closed     => '已关闭',
            self::Evaluating => '待评估',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'gray',
            self::Processing => 'info',
            self::Verifying  => 'warning',
            self::Closed     => 'success',
            self::Evaluating => 'gray',
        };
    }

    // 允许流转到的下一状态（状态机）
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending    => [self::Processing],
            self::Processing => [self::Verifying],
            self::Verifying  => [self::Closed, self::Processing], // 通过=closed；不通过=退回
            self::Closed     => [],
            self::Evaluating => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }
}
