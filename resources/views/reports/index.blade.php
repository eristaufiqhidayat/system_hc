@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
    <x-page-head :eyebrow="month_id($from->month).' – '.month_id($to->month).' '.$to->year.' ·'" title="Laporan">
        <x-slot:actions>
            <form method="GET" action="{{ route('reports.index') }}">
                <select name="periode" style="width:180px" aria-label="Periode" data-autosubmit>
                    @foreach ($periods as $k => $v)<option value="{{ $k }}" @selected($k === $period)>{{ $v }}</option>@endforeach
                </select>
            </form>
            <a class="btn" href="{{ route('reports.print', ['periode' => $period]) }}" target="_blank"><x-icon name="download"/>PDF</a>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi :label="'Omzet '.month_id(now()->month, false)" :value="rupiah_short($revenueThisMonth)"><x-delta :value="$revenueDelta"/> dari {{ month_id(now()->subMonthNoOverflow()->month, false) }}</x-kpi>
        <x-kpi :label="'Porsi terjual '.$portionsMonthLabel" :value="qty($portionsTotal)">Rata-rata {{ qty($portionsDaily) }} per hari</x-kpi>
        <x-kpi label="HPP rata-rata" :value="$totals['hpp_percent'] !== null ? str_replace('.', ',', $totals['hpp_percent']).'%' : '—'">Dari harga jual</x-kpi>
        <x-kpi label="Margin kotor" :value="$totals['margin_percent'] !== null ? str_replace('.', ',', $totals['margin_percent']).'%' : '—'">Periode terpilih</x-kpi>
    </div>

    <div class="g21">
        <div class="card"><div class="between"><h2>Omzet bulanan (juta rupiah)</h2><span class="sub">Arahkan kursor ke titik</span></div>
            <x-line-chart :labels="$chart['labels']" :values="$chart['values']" unit="jt" label="Grafik omzet bulanan"/>
        </div>
        <div class="card"><h2>Porsi {{ $portionsMonthLabel }} per lini</h2>
            <x-bar-chart :rows="$portionsLine"/>
        </div>
    </div>

    <div class="g2">
        <div class="card pad0"><div style="padding:18px 20px"><h2>Menu terlaris</h2></div>
            <table><thead><tr><th>Menu</th><th class="num">Porsi</th><th>Rating pelanggan</th></tr></thead><tbody>
                @forelse ($topMenus as $m)
                    <tr><td class="strong">{{ $m['name'] }}</td><td class="num">{{ qty($m['portions']) }}</td><td>{{ $m['rating'] ? '★ '.str_replace('.', ',', $m['rating']) : '—' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="empty">Belum ada data produksi</td></tr>
                @endforelse
            </tbody></table>
        </div>
        <div class="card pad0"><div style="padding:18px 20px"><h2>Profitabilitas per lini</h2></div>
            <table><thead><tr><th>Lini</th><th class="num">Omzet</th><th class="num">HPP</th><th class="num">Margin</th></tr></thead><tbody>
                @foreach ($profitability as $p)
                    <tr><td class="strong">{{ $p['line'] }}</td><td class="num">{{ rupiah_short($p['revenue']) }}</td><td class="num">{{ $p['hpp_percent'] !== null ? str_replace('.', ',', $p['hpp_percent']).'%' : '—' }}</td><td class="num">{{ $p['margin_percent'] !== null ? str_replace('.', ',', $p['margin_percent']).'%' : '—' }}</td></tr>
                @endforeach
            </tbody></table>
            <div class="note" style="margin:14px 16px 16px">Angka HPP & margin terisi otomatis dari resep dan harga beli bahan (lihat Menu & Resep).</div>
        </div>
    </div>
@endsection
