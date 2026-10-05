@extends('layouts.print')
@section('title', 'Laporan')

@section('content')
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="row"><img src="{{ asset('images/logo.png') }}" alt="" style="height:48px"><div class="col"><h1>Laporan {{ config('catering.name') }}</h1><span class="muted">{{ $periods[$period] }} · {{ date_id($from, true) }} – {{ date_id($to, true) }}</span></div></div>
        <div class="g4">
            <x-kpi :label="'Omzet '.month_id(now()->month, false)" :value="rupiah_short($revenueThisMonth)"><x-delta :value="$revenueDelta"/></x-kpi>
            <x-kpi :label="'Porsi '.$portionsMonthLabel" :value="qty($portionsTotal)">Rata-rata {{ qty($portionsDaily) }}/hari</x-kpi>
            <x-kpi label="HPP rata-rata" :value="$totals['hpp_percent'] !== null ? $totals['hpp_percent'].'%' : '—'"> </x-kpi>
            <x-kpi label="Margin kotor" :value="$totals['margin_percent'] !== null ? $totals['margin_percent'].'%' : '—'"> </x-kpi>
        </div>
        <div class="card"><h2>Omzet bulanan (juta rupiah)</h2><x-line-chart :labels="$chart['labels']" :values="$chart['values']" unit="jt"/></div>
        <div class="g2">
            <div class="card pad0"><div style="padding:14px 16px"><h2>Menu terlaris</h2></div>
                <table><tbody>@foreach ($topMenus as $m)<tr><td>{{ $m['name'] }}</td><td class="num">{{ qty($m['portions']) }}</td></tr>@endforeach</tbody></table></div>
            <div class="card pad0"><div style="padding:14px 16px"><h2>Profitabilitas per lini</h2></div>
                <table><tbody>@foreach ($profitability as $p)<tr><td>{{ $p['line'] }}</td><td class="num">{{ rupiah($p['revenue']) }}</td><td class="num">{{ $p['margin_percent'] ?? '—' }}%</td></tr>@endforeach</tbody></table></div>
        </div>
    </div>
@endsection
