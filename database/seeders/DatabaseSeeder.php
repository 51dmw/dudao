<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DimensionSeeder::class,  // 5 维度 + 13 检查项模板
            DemoSeeder::class,       // 管理员 + 团队 + 演示网站
        ]);
    }
}
