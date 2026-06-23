# 网站质量管理工作台 · 后端结构（Laravel 11 + Filament 3）

> 选型理由：小团队（2-3 人质检）后台，Filament 提供开箱即用的资源管理、表单、表格、筛选、权限，**几乎不写前端**即可覆盖原型里 90% 的界面。SQL 用 MySQL 8。

## 一、技术栈

| 层 | 选型 |
|---|---|
| 框架 | Laravel 11 (PHP 8.2+) |
| 后台 UI | Filament 3（管理面板，含表单/表格/Widget/权限） |
| 数据库 | MySQL 8.0 / utf8mb4 |
| 权限 | filament-shield（基于 spatie/laravel-permission） |
| 通知 | Laravel Notification + 自定义 Telegram Channel（第二阶段） |
| 任务 | Laravel Scheduler（每周自动生成周报、超期问题提醒） |

## 二、目录结构

```
website-qa-center/
├── app/
│   ├── Models/                      # 与 schema.sql 一一对应
│   │   ├── User.php
│   │   ├── Website.php
│   │   ├── Dimension.php
│   │   ├── CheckItem.php
│   │   ├── Inspection.php
│   │   ├── InspectionResult.php
│   │   ├── Issue.php
│   │   ├── IssueAttachment.php
│   │   ├── IssueLog.php
│   │   └── Suggestion.php
│   │
│   ├── Enums/                       # 用枚举固化状态/等级，避免魔法字符串
│   │   ├── Role.php                 # admin/supervisor/pm/operator/seo/manager
│   │   ├── IssueLevel.php           # P0/P1/P2/P3 (+时限天数)
│   │   ├── IssueStatus.php          # pending→processing→verifying→closed
│   │   └── Grade.php                # A/B/C/D/E (+分数区间)
│   │
│   ├── Services/                    # 业务逻辑（不写进 Controller/Model）
│   │   ├── ScoringService.php       # 评分引擎：明细→各维度分→总分→等级
│   │   ├── IssueFactory.php         # 巡检异常项 → 批量生成问题单(待指派)
│   │   ├── IssueWorkflow.php        # 问题状态机流转 + 写 issue_logs
│   │   └── ReportService.php        # 周报/月报/排行榜聚合查询
│   │
│   ├── Filament/
│   │   ├── Resources/               # = 原型的各模块页面
│   │   │   ├── WebsiteResource.php       # 网站档案
│   │   │   ├── InspectionResource.php    # 巡检表单(含检查项 Repeater + 实时算分)
│   │   │   ├── IssueResource.php         # 问题中心(筛选/分派下拉/状态流转 Action)
│   │   │   ├── SuggestionResource.php    # 优化池
│   │   │   └── UserResource.php          # 人员管理
│   │   │
│   │   ├── Widgets/                  # = 管理驾驶舱 / 报表
│   │   │   ├── KpiStatsWidget.php        # 5 个核心指标卡
│   │   │   ├── QualityRankWidget.php     # 质量排行榜
│   │   │   ├── RiskSitesWidget.php       # 高风险网站
│   │   │   ├── IssueLevelChartWidget.php # 问题等级分布
│   │   │   └── ScoreTrendWidget.php      # 月度质量分趋势
│   │   │
│   │   └── Pages/
│   │       ├── Dashboard.php             # 驾驶舱(挂载上面的 Widget)
│   │       ├── WeeklyReport.php          # 周报中心
│   │       ├── MonthlyReview.php         # 月度复盘
│   │       └── Performance.php           # 人员绩效排行
│   │
│   └── Policies/                    # 角色权限：谁能建巡检/验收/分派
│       ├── InspectionPolicy.php
│       └── IssuePolicy.php
│
├── database/
│   ├── migrations/                  # 由 schema.sql 拆分而来(见下表)
│   ├── seeders/
│   │   ├── DimensionSeeder.php      # 5 维度
│   │   ├── CheckItemSeeder.php      # 13 检查项模板
│   │   └── DemoSeeder.php           # 演示数据(网站/人员/问题)
│   └── schema.sql                   # ← 已交付的完整建表脚本(参考/直接导入)
│
├── routes/
│   └── web.php                      # Filament 自动注册，几乎不用手写路由
│
└── config/
    └── qa.php                       # 评分权重、等级区间、各等级时限 等配置
```

## 三、Migration 拆分对照（按依赖顺序）

| 顺序 | Migration 文件 | 对应表 |
|---|---|---|
| 1 | `xxxx_create_users_table` | users |
| 2 | `xxxx_create_websites_table` | websites |
| 3 | `xxxx_create_dimensions_table` | dimensions |
| 4 | `xxxx_create_check_items_table` | check_items |
| 5 | `xxxx_create_inspections_table` | inspections |
| 6 | `xxxx_create_inspection_results_table` | inspection_results |
| 7 | `xxxx_create_issues_table` | issues |
| 8 | `xxxx_create_issue_attachments_table` | issue_attachments |
| 9 | `xxxx_create_issue_logs_table` | issue_logs |
| 10 | `xxxx_create_suggestions_table` | suggestions |

> 外键依赖：users → websites → (dimensions, check_items) → inspections → inspection_results / issues → 其余。按此顺序建表即可。

## 四、三个核心 Service 的职责（业务魂）

### 1. ScoringService — 评分引擎
```
输入：inspection_id（含所有 inspection_results 勾选结果）
逻辑：
  按 check_item.dimension 分组 → 累加 is_normal=1 的 points → 得各维度分
  total = 各维度分之和 + score_adjust(整改加减分)
  grade = Grade::fromScore(total)   // A≥90 B≥80 C≥70 D≥60 E<60
输出：回写 inspections 各维度分/总分/等级 + 更新 websites 缓存分
```

### 2. IssueFactory — 巡检转问题（原型里"提交巡检自动生成问题单"）
```
输入：inspection（已提交）
逻辑：
  遍历 is_normal=0 的明细
  每条 → 建 issue：
    level    = check_item.default_level
    type     = 维度映射(product/content/ad/ux...)
    title    = "[检查项名] 检查发现异常"
    due_at   = now + IssueLevel::hours()   // P0立即 P1=24h P2=72h P3不限
    status   = pending, assignee = null(待指派)
    inspection_id 关联回溯
```

### 3. IssueWorkflow — 状态机（原型里点击状态推进）
```
合法流转：pending → processing → verifying → closed
          verifying → processing (验收不通过, repeat_count++)
每次流转：写 issue_logs(from,to,operator) + 维护 closed_at
权限：仅 supervisor/admin 可 closed(验收)；pm/operator 只能推进到 verifying
```

## 五、落地命令（开发起步）

```bash
# 1. 建项目
composer create-project laravel/laravel website-qa-center
cd website-qa-center
composer require filament/filament filament-shield

# 2. 导入表结构（任选其一）
mysql -u root -p qa_center < database/schema.sql      # 直接导入
# 或 php artisan migrate（拆成 migration 后）

# 3. 装后台 & 建管理员
php artisan filament:install --panels
php artisan make:filament-user

# 4. 灌初始维度/检查项
php artisan db:seed --class=DimensionSeeder
php artisan db:seed --class=CheckItemSeeder
```

## 六、与原型的映射速查

| 原型模块 | Laravel 实现 |
|---|---|
| 管理驾驶舱 | `Pages/Dashboard` + 5 个 Widget |
| 网站档案 | `WebsiteResource`（Table + Form） |
| 巡检表单（实时算分） | `InspectionResource` + Repeater + `ScoringService`（livewire 实时计算） |
| 提交→生成问题 | `InspectionResource` 的提交 Action 调 `IssueFactory` |
| 问题中心（分派/流转） | `IssueResource` + 行内分派 Select + 状态流转 Action（调 `IssueWorkflow`） |
| 周报/月度复盘 | `WeeklyReport` / `MonthlyReview` Page + `ReportService` |
| 人员绩效排行 | `Performance` Page（聚合 inspections / issue_logs） |
