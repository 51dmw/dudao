<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DimensionSeeder::class,  // 5 评分维度
            ChecklistSeeder::class,  // 巡检项目录：页面类型→模块→巡检项(~150)
            DemoSeeder::class,       // 管理员 + 团队 + 演示网站/问题/巡检
        ]);
    }
}
