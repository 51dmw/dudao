<x-filament-panels::page>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        {{-- 督导巡检量 --}}
        <x-filament::section>
            <x-slot name="heading">督导巡检量排行</x-slot>
            @if ($workload->isEmpty())
                <div style="color:#9ca3af;font-size:14px;">本月暂无巡检记录</div>
            @else
                <table style="width:100%;font-size:14px;">
                    @foreach ($workload as $i => $u)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:7px 0;">{{ $i + 1 }}. {{ $u->name }}</td>
                            <td style="padding:7px 0;text-align:right;font-weight:600;">{{ $u->inspections_count }} 次</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 整改效率 --}}
        <x-filament::section>
            <x-slot name="heading">整改效率（关闭率 / 平均时长）</x-slot>
            @if ($efficiency->isEmpty())
                <div style="color:#9ca3af;font-size:14px;">暂无指派给责任人的问题</div>
            @else
                <table style="width:100%;font-size:14px;">
                    @foreach ($efficiency as $i => $e)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:7px 0;">{{ $i + 1 }}. {{ $e->name }}</td>
                            <td style="padding:7px 0;text-align:right;font-weight:600;">
                                {{ $e->close_rate }}%
                                <span style="color:{{ $e->avg_hours !== null && $e->avg_hours > 48 ? '#d97706' : '#9ca3af' }};font-weight:400;">
                                    · {{ $e->avg_hours !== null ? $e->avg_hours.'h' : '—' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
