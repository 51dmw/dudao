<x-filament-panels::page>
    <div style="font-size:13px;color:#6b7280;margin-top:-8px;">
        {{ now()->subDays(7)->format('Y-m-d') }} ~ {{ now()->format('Y-m-d') }} · 自动生成
    </div>

    {{-- 核心数字 --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
        @php
            $cards = [
                ['本周新增问题', $stats['new'], '#2563eb'],
                ['本周已关闭', $stats['closed'], '#16a34a'],
                ['当前待整改', $stats['open'], '#d97706'],
                ['P0 问题', $stats['p0'], $stats['p0'] ? '#dc2626' : '#16a34a'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $color])
            <x-filament::section>
                <div style="font-size:13px;color:#6b7280;">{{ $label }}</div>
                <div style="font-size:28px;font-weight:600;color:{{ $color }};line-height:1.2;">{{ $value }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        {{-- 质量排行榜 --}}
        <x-filament::section>
            <x-slot name="heading">质量排行榜 Top5</x-slot>
            <table style="width:100%;font-size:14px;">
                @foreach ($ranking as $i => $w)
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td style="padding:6px 0;">{{ $i + 1 }}. {{ $w->name }}</td>
                        <td style="padding:6px 0;text-align:right;font-weight:600;">{{ $w->current_score }}
                            <span style="color:#9ca3af;font-weight:400;">{{ $w->current_grade?->value }}</span>
                        </td>
                    </tr>
                @endforeach
            </table>
        </x-filament::section>

        {{-- 问题等级分布 --}}
        <x-filament::section>
            <x-slot name="heading">问题等级分布</x-slot>
            @php $max = max(1, max($levels)); $colors = ['P0'=>'#dc2626','P1'=>'#dc2626','P2'=>'#d97706','P3'=>'#2563eb']; @endphp
            @foreach (['P0','P1','P2','P3'] as $lv)
                <div style="display:flex;align-items:center;gap:10px;margin:6px 0;font-size:13px;">
                    <span style="width:28px;color:#6b7280;">{{ $lv }}</span>
                    <div style="flex:1;background:#f3f4f6;border-radius:4px;height:16px;">
                        <div style="width:{{ round($levels[$lv] / $max * 100) }}%;height:16px;border-radius:4px;background:{{ $colors[$lv] }};"></div>
                    </div>
                    <span style="width:24px;text-align:right;font-weight:600;">{{ $levels[$lv] }}</span>
                </div>
            @endforeach
        </x-filament::section>
    </div>

    {{-- 高风险网站 --}}
    <x-filament::section>
        <x-slot name="heading">高风险网站（评分 &lt; 70）</x-slot>
        @if ($risks->isEmpty())
            <div style="color:#16a34a;font-size:14px;">暂无高风险网站</div>
        @else
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                @foreach ($risks as $w)
                    <div style="border:1px solid #fecaca;background:#fef2f2;border-radius:8px;padding:10px 14px;">
                        <div style="font-weight:600;">{{ $w->name }}</div>
                        <div style="color:#dc2626;font-size:20px;font-weight:600;">{{ $w->current_score }}
                            <span style="font-size:13px;">{{ $w->current_grade?->value }}</span></div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
