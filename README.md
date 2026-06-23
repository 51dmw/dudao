# 网站质量管理工作台 (Website QA Center)

督导组工作平台 · Laravel 11 + Filament 3 + MySQL 8

## 已交付内容（可运行骨架）

```
website-qa-center/
├── README.md                         ← 本文件
├── config/qa.php                     业务规则配置(评分权重/SLA/状态机)
├── database/
│   ├── schema.sql                    完整建表脚本(可直接导入)
│   ├── migrations/                   10 个 Laravel 迁移(对应 10 张表)
│   └── seeders/                      维度/检查项 + 演示数据
├── app/
│   ├── Enums/                        Role / Grade / IssueLevel / IssueStatus
│   ├── Models/                       10 个 Eloquent 模型(含关系/cast)
│   └── Services/                     评分引擎 / 巡检转问题 / 状态机
└── docs/backend-structure.md         完整后端结构与模块映射
```

> 本目录是「源码片段」，不是完整 Laravel 工程（缺 vendor/、框架骨架）。
> 按下面步骤拷进一个新建的 Laravel 项目即可跑起来。

## 一、起步（在装有 PHP 8.2+ / Composer / MySQL 的环境）

```bash
# 1. 新建 Laravel 工程
composer create-project laravel/laravel qa-center
cd qa-center

# 2. 把本目录的 app/、config/qa.php、database/migrations、database/seeders 覆盖进去
#    并删除框架自带的 0001_01_01_000000_create_users_table.php(本项目自带 users 迁移)

# 3. 配置 .env 数据库连接
#    DB_DATABASE=qa_center  DB_USERNAME=...  DB_PASSWORD=...

# 4. 建表 + 灌初始数据
php artisan migrate --seed

# 5. 装 Filament 后台
composer require filament/filament
php artisan filament:install --panels
```

默认管理员（DemoSeeder 生成）：`admin@qa.local` / `password`

## 二、迁移执行顺序

迁移文件名已带序号，`migrate` 会按 users → websites → dimensions → check_items
→ inspections → inspection_results → issues → attachments → logs → suggestions 顺序建表，
外键依赖自动满足。

## 三、核心业务怎么串（代码入口）

```php
// 巡检提交：算分 + 自动生成问题单
$inspection = Inspection::with('results.checkItem.dimension')->find($id);
app(ScoringService::class)->calculate($inspection);        // 回写各维度分/总分/等级/网站缓存
$issues = app(IssueFactory::class)->generateFromInspection($inspection); // 异常项→问题单(待指派)

// 问题流转：状态机推进 + 审计日志
app(IssueWorkflow::class)->transition($issue, IssueStatus::Processing, $operator);
app(IssueWorkflow::class)->assign($issue, $assignee, $operator);
```

## 四、下一步（待补，进入界面层）

- `app/Filament/Resources/*` — 网站/巡检/问题/优化池/人员 的后台 CRUD
- `app/Filament/Widgets/*` — 驾驶舱指标卡、排行榜、问题分布图
- `app/Filament/Pages/*` — 周报 / 月度复盘 / 人员绩效
- `app/Policies/*` — 角色权限（谁能验收/分派）

参见 `docs/backend-structure.md` 的逐模块映射表。
