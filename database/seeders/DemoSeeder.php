<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Website;
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

        foreach ($sites as [$name, $domain, $score, $grade]) {
            Website::create([
                'name' => $name, 'domain' => $domain,
                'pm_id' => $team->where('role', 'pm')->random()->id,
                'operator_id' => $team->where('role', 'operator')->random()->id,
                'current_score' => $score, 'current_grade' => $grade,
                'status' => $score < 70 ? 'warning' : 'normal',
                'last_inspected_at' => now(),
            ]);
        }
    }
}
