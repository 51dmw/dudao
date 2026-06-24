<?php

namespace Database\Seeders;

use App\Enums\IssueStatus;
use App\Models\Inspection;
use App\Models\Issue;
use App\Models\User;
use App\Models\Website;
use App\Services\IssueWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 演示数据：管理员 + 督导团队 + 几个网站。
 * 注意：金斗已离职，不纳入团队名单。
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => '管理员', 'email' => 'admin@qa.local',
            'password' => Hash::make('password'), 'role' => 'admin',
        ]);

        $team = collect([
            ['张三', 'supervisor'], ['李四', 'operator'], ['王五', 'pm'],
            ['陈六', 'operator'], ['周八', 'operator'], ['吴九', 'pm'],
        ])->map(fn ($u, $i) => User::create([
            'name' => $u[0], 'email' => 'user' . ($i + 1) . '@qa.local',
            'password' => Hash::make('password'), 'role' => $u[1],
        ]));

        $sites = [
            ['91JAV', '91jav.com', 95, 'A'],
            ['51吃瓜', '51cg.com', 86, 'B'],
            ['海角网', 'haijiao.com', 81, 'B'],
            ['瓜报社', 'guabao.com', 74, 'C'],
            ['今日大瓜', 'todaygua.com', 65, 'D'],
            ['黑料不打烊', 'heiliao.com', 58, 'E'],
        ];

        $websites = collect();
        foreach ($sites as [$name, $domain, $score, $grade]) {
            $websites->push(Website::create([
                'name' => $name, 'domain' => $domain,
                'pm_id' => $team->where('role', 'pm')->random()->id,
                'operator_id' => $team->where('role', 'operator')->random()->id,
                'current_score' => $score, 'current_grade' => $grade,
                'status' => $score < 70 ? 'warning' : 'normal',
                'last_inspected_at' => now(),
            ]));
        }

        $this->seedIssues($websites, $team, $admin);
        $this->seedInspections($websites, $team);
    }

    /** 造一批历史巡检记录，填充「督导巡检量」和「月均分趋势」 */
    private function seedInspections(\Illuminate\Support\Collection $websites, \Illuminate\Support\Collection $team): void
    {
        $inspectors = $team->whereIn('id', $team->take(3)->pluck('id'))->values(); // 张三/李四/王五
        $monthScores = [
            ['2026-04', [78, 80, 82]],
            ['2026-05', [80, 83, 85]],
            ['2026-06', [84, 86, 88]],
        ];

        foreach ($monthScores as [$ym, $scores]) {
            foreach ($scores as $i => $score) {
                $grade = $score >= 90 ? 'A' : ($score >= 80 ? 'B' : ($score >= 70 ? 'C' : 'D'));
                Inspection::create([
                    'website_id'    => $websites->get($i % $websites->count())->id,
                    'inspector_id'  => $inspectors->get($i % $inspectors->count())->id,
                    'inspect_date'  => $ym . '-' . str_pad((string) (5 + $i * 3), 2, '0', STR_PAD_LEFT),
                    'score_product' => 25, 'score_content' => 21, 'score_ux' => 17,
                    'score_ad' => 13, 'score_exec' => 8,
                    'total_score'   => $score, 'grade' => $grade, 'status' => 'reviewed',
                ]);
            }
        }
    }

    /** 造一批演示问题，并把部分流转到关闭，让报表/绩效有数据 */
    private function seedIssues(\Illuminate\Support\Collection $websites, \Illuminate\Support\Collection $team, User $admin): void
    {
        $supervisor = $team->first(fn ($u) => $u->role->value === 'supervisor');
        $handlers   = $team->filter(fn ($u) => in_array($u->role->value, ['operator', 'pm']))->values();
        $wf         = app(IssueWorkflow::class);

        // [网站索引, 优先级, 分类, 页面类型, 终端, 标题, 整改建议, 处理进度]
        $defs = [
            [4, 'P0', 'product',  'home',     'all',    '首页打开白屏，核心模块加载失败', '排查首页接口超时，加容错降级',   'processing'],
            [3, 'P1', 'ad',       'category', 'pc',     '分类页前三条全是广告，遮挡内容', '前三条至少保留 2 条内容',         'closed'],
            [2, 'P1', 'content',  'category', 'all',    '娱乐分类近两周零更新',           '补充近两周内容，恢复更新节奏',   'verifying'],
            [3, 'P2', 'ux',       'article',  'mobile', '移动端文章页图片错位',           '修复移动端图片宽度自适应',       'closed'],
            [1, 'P2', 'product',  'article',  'all',    '相关推荐模块点击无跳转',         '修复推荐位链接绑定',             'reject_then_close'],
            [5, 'P1', 'content',  'article',  'all',    '大量文章缺图、排版混乱',         '统一排版模板，补齐配图',         'processing'],
            [5, 'P2', 'ux',       'article',  'mobile', '广告频率过高影响阅读',           '降低文中广告插入频率',           'pending'],
            [4, 'P2', 'product',  'search',   'all',    '搜索结果分页失效',               '修复分页参数传递',               'closed'],
            [2, 'P3', 'operation','home',     'all',    '建议首页增加热门专题聚合位',     '评估首页增加专题模块',           'pending'],
            [0, 'P2', 'seo',      'article',  'all',    '部分文章页缺少 title 标签',      '补全文章页 title 标签',          'closed'],
        ];

        $seq = 0;
        foreach ($defs as [$wi, $level, $type, $pageType, $device, $title, $fix, $flow]) {
            $seq++;
            $assignee = $flow === 'pending' ? null : $handlers->get($seq % $handlers->count());
            $issue = Issue::create([
                'code'           => '#' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
                'website_id'     => $websites->get($wi)->id,
                'level'          => $level,
                'type'           => $type,
                'page_type'      => $pageType,
                'device'         => $device,
                'title'          => $title,
                'fix_suggestion' => $fix,
                'reporter_id'    => $supervisor->id,
                'assignee_id'    => $assignee?->id,
                'due_at'         => $level === 'P3' ? null : now()->addDays($level === 'P1' ? 1 : 3),
                'status'         => IssueStatus::Pending->value,
                'created_at'     => now()->subDays(rand(1, 6)),
            ]);

            if (! $assignee) {
                continue;
            }

            match ($flow) {
                'processing' => $wf->transition($issue, IssueStatus::Processing, $assignee),
                'verifying'  => $this->flow($wf, $issue, $assignee, ['processing', 'verifying']),
                'closed'     => $this->flow($wf, $issue, $assignee, ['processing', 'verifying', 'closed']),
                'reject_then_close' => $this->flow($wf, $issue, $assignee,
                    ['processing', 'verifying', 'processing', 'verifying', 'closed']),
                default => null,
            };
        }
    }

    private function flow(IssueWorkflow $wf, Issue $issue, User $by, array $steps): void
    {
        foreach ($steps as $s) {
            $wf->transition($issue, IssueStatus::from($s), $by);
        }
    }
}
