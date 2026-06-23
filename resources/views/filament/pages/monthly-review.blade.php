<x-filament-panels::page>
    {{-- 月度核心指标 --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
        @php
            $cards = [
                ['月均质量分', $metrics['avg_score'], '#2563eb'],
                ['问题关闭率', $metrics['close_rate'].'%', '#16a34a'],
                ['重复问题率', $metrics['repeat_rate'].'%', $metrics['repeat_rate'] > 15 ? '#dc2626' : '#d97706'],
                ['巡检覆盖率', $metrics['coverage'].'%', '#2563eb'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $color])
            <x-filament::section>
                <div style="font-size:13px;color:#6b7280;">{{ $label }}</div>
                <div style="font-size:28px;font-weight:600;color:{{ $color }};line-height:1.2;">{{ $value }}</div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- 月均质量分趋势 --}}
    <x-filament::section>
        <x-slot name="heading">月均质量分趋势</x-slot>
        @php
            $vals = array_column($trend, 'value');
            $mn = $vals ? min($vals) : 0; $mx = $vals ? max($vals) : 100;
            $span = max(1, $mx - $mn);
        @endphp
        <div style="display:flex;align-items:flex-end;gap:14px;height:160px;padding-top:10px;">
            @foreach ($trend as $i => $t)
                @php $h = 30 + round(($t['value'] - $mn) / $span * 110); $last = $i === count($trend) - 1; @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <span style="font-size:12px;font-weight:600;">{{ $t['value'] }}</span>
                    <div style="width:100%;max-width:60px;height:{{ $h }}px;border-radius:6px 6px 0 0;background:{{ $last ? '#2563eb' : '#dbeafe' }};"></div>
                    <span style="font-size:12px;color:#9ca3af;">{{ $t['label'] }}</span>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
