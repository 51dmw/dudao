<?php

/*
 * 网站质量管理工作台 · 业务规则配置
 * 评分权重、等级区间、问题等级时限——集中在此，便于不改代码即可调参。
 */

return [

    // 5 大评分维度满分（与 dimensions 表保持一致，总分 100）
    'dimensions' => [
        'product' => ['label' => '产品质量',  'max' => 30],
        'content' => ['label' => '内容运营',  'max' => 25],
        'ux'      => ['label' => '用户体验',  'max' => 20],
        'ad'      => ['label' => '商业化运营','max' => 15],
        'exec'    => ['label' => '运营执行',  'max' => 10],
    ],

    // 等级区间：grade => [最低分, 最高分]
    'grades' => [
        'A' => [90, 100],
        'B' => [80, 89],
        'C' => [70, 79],
        'D' => [60, 69],
        'E' => [0, 59],
    ],

    // 问题等级 => 处理时限（小时）。null = 不限（P3 优化建议）
    'issue_sla_hours' => [
        'P0' => 0,    // 立即处理
        'P1' => 24,   // 24 小时内
        'P2' => 72,   // 3 天内
        'P3' => null, // 优化建议，不计时
    ],

    // 问题状态机：当前状态 => 允许流转到的状态
    'issue_transitions' => [
        'pending'    => ['processing'],
        'processing' => ['verifying'],
        'verifying'  => ['closed', 'processing'], // 验收通过=closed；不通过=退回 processing
        'closed'     => [],
        'evaluating' => ['accepted', 'rejected'], // 优化建议线
    ],

    // 整改加减分规则（写入 inspections.score_adjust）
    'adjust' => [
        'close_rate_bonus_threshold' => 0.9,  // 关闭率≥90% 加分
        'close_rate_bonus'           => 2,
        'repeat_rate_penalty_threshold' => 0.2, // 重复问题率>20% 扣分
        'repeat_rate_penalty'        => -3,
    ],
];
