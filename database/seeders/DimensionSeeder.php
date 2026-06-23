<?php

namespace Database\Seeders;

use App\Models\CheckItem;
use App\Models\Dimension;
use Illuminate\Database\Seeder;

class DimensionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => '产品质量',  'code' => 'product', 'max' => 30, 'items' => [
                ['页面结构与展示', 10, 'P2'], ['功能完整性', 10, 'P1'], ['产品体验', 10, 'P2'],
            ]],
            ['name' => '内容运营',  'code' => 'content', 'max' => 25, 'items' => [
                ['内容更新', 10, 'P1'], ['内容质量', 10, 'P2'], ['内容运营策略', 5, 'P3'],
            ]],
            ['name' => '用户体验',  'code' => 'ux', 'max' => 20, 'items' => [
                ['浏览体验', 10, 'P2'], ['移动端体验', 5, 'P2'], ['用户路径体验', 5, 'P3'],
            ]],
            ['name' => '商业化运营','code' => 'ad', 'max' => 15, 'items' => [
                ['广告配置检查', 10, 'P1'], ['广告体验检查', 5, 'P2'],
            ]],
            ['name' => '运营执行',  'code' => 'exec', 'max' => 10, 'items' => [
                ['页面维护情况', 5, 'P3'], ['需求执行情况', 5, 'P3'],
            ]],
        ];

        foreach ($data as $sort => $d) {
            $dim = Dimension::create([
                'name'      => $d['name'],
                'code'      => $d['code'],
                'max_score' => $d['max'],
                'sort'      => $sort + 1,
            ]);

            foreach ($d['items'] as $i => [$name, $points, $level]) {
                CheckItem::create([
                    'dimension_id'  => $dim->id,
                    'name'          => $name,
                    'points'        => $points,
                    'default_level' => $level,
                    'sort'          => $i + 1,
                ]);
            }
        }
    }
}
