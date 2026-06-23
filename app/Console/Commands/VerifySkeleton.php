<?php

namespace App\Console\Commands;

use App\Enums\IssueStatus;
use App\Models\CheckItem;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Website;
use App\Services\IssueFactory;
use App\Services\IssueWorkflow;
use App\Services\ScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 骨架验证：模拟「巡检 → 算分 → 生成问题 → 状态流转」全链路并断言结果。
 * 用法：php artisan qa:verify
 */
class VerifySkeleton extends Command
{
    protected $signature = 'qa:verify';
    protected $description = '验证 ScoringService / IssueFactory / IssueWorkflow 全链路';

    private int $pass = 0;
    private int $fail = 0;

    public function handle(): int
    {
        DB::beginTransaction();

        $website   = Website::first() ?? $this->fail('没有网站数据，请先 migrate --seed') ;
        $inspector = User::where('role', 'supervisor')->first() ?? User::first();

        // 1) 造一次巡检：故意把 2 个检查项判为异常
        $inspection = Inspection::create([
            'website_id'   => $website->id,
            'inspector_id' => $inspector->id,
            'inspect_date' => now()->toDateString(),
            'status'       => 'submitted',
        ]);

        $abnormal = ['功能完整性', '广告配置检查']; // 默认 P1 / P1
        foreach (CheckItem::all() as $item) {
            $inspection->results()->create([
                'check_item_id' => $item->id,
                'is_normal'     => ! in_array($item->name, $abnormal, true),
            ]);
        }

        // 2) 评分引擎
        app(ScoringService::class)->calculate($inspection);
        $inspection->refresh();
        // 满分 100，扣掉 功能完整性(10) + 广告配置(10) = 80 → B
        $this->assert($inspection->total_score == 80.0, "总分应为 80，实际 {$inspection->total_score}");
        $this->assert($inspection->grade->value === 'B', "等级应为 B，实际 {$inspection->grade->value}");
        $this->assert($website->fresh()->current_score == 80.0, '网站缓存分应同步为 80');

        // 3) 巡检转问题
        $issues = app(IssueFactory::class)->generateFromInspection($inspection);
        $this->assert($issues->count() === 2, "应生成 2 个问题，实际 {$issues->count()}");
        $first = $issues->first();
        $this->assert($first->level->value === 'P1', "首个问题等级应为 P1，实际 {$first->level->value}");
        $this->assert($first->assignee_id === null, '新问题应为待指派(assignee=null)');
        $this->assert($first->due_at !== null, 'P1 问题应有截止时间');
        $this->assert($first->status === IssueStatus::Pending, '新问题状态应为 pending');

        // 4) 状态机：合法流转
        $wf = app(IssueWorkflow::class);
        $wf->assign($first, $inspector, $inspector);
        $this->assert($first->fresh()->assignee_id === $inspector->id, '指派后责任人应被设置');

        $wf->transition($first, IssueStatus::Processing, $inspector);
        $wf->transition($first, IssueStatus::Verifying, $inspector);
        // 验收不通过 → 退回处理中，repeat_count +1
        $wf->transition($first, IssueStatus::Processing, $inspector);
        $this->assert($first->fresh()->repeat_count === 1, '验收打回应使 repeat_count=1');

        // 再走完整闭环
        $wf->transition($first, IssueStatus::Verifying, $inspector);
        $wf->transition($first, IssueStatus::Closed, $inspector);
        $this->assert($first->fresh()->status === IssueStatus::Closed, '最终状态应为 closed');
        $this->assert($first->fresh()->closed_at !== null, 'closed 应记录 closed_at');
        $this->assert($first->logs()->count() >= 5, '应有完整的流转审计日志');

        // 5) 状态机：非法流转应抛异常
        $illegal = false;
        try {
            $wf->transition($issues->last(), IssueStatus::Closed, $inspector); // pending→closed 非法
        } catch (\Throwable $e) {
            $illegal = true;
        }
        $this->assert($illegal, 'pending→closed 非法流转应被拦截');

        DB::rollBack(); // 验证用，不污染数据

        $this->newLine();
        $this->info("通过 {$this->pass} 项，失败 {$this->fail} 项");
        return $this->fail === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function assert(bool $ok, string $msg): void
    {
        if ($ok) {
            $this->pass++;
            $this->line("  <fg=green>✓</> {$msg}");
        } else {
            $this->fail++;
            $this->line("  <fg=red>✗ {$msg}</>");
        }
    }
}
