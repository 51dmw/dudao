<?php

namespace Database\Seeders;

use App\Models\Dimension;
use Illuminate\Database\Seeder;

/**
 * 5 大评分维度（用于分类归口与报表）。
 * 巡检项目录见 ChecklistSeeder（页面类型→模块→巡检项）。
 */
class DimensionSeeder extends Seeder
{
    public function run(): void
    {
        $dims = [
            ['产品质量', 'product', 30],
            ['内容运营', 'content', 25],
            ['用户体验', 'ux', 20],
            ['商业化运营', 'ad', 15],
            ['运营执行', 'exec', 10],
        ];

        foreach ($dims as $sort => [$name, $code, $max]) {
            Dimension::create([
                'name' => $name, 'code' => $code, 'max_score' => $max, 'sort' => $sort + 1,
            ]);
        }
    }
}
