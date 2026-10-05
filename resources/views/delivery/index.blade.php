@extends('layouts.app')
@section('title', 'Pengiriman')

@section('content')
    <x-page-head eyebrow="Rute disusun otomatis dari alamat pesanan ·" :title="'Pengiriman · '.date_id($date)">
        <x-slot:actions>
            @if ($selected)
                <form method="POST" action="{{ route('delivery.optimize', $selected) }}">@csrf<button class="btn">Optimalkan rute</button></form>
            @endif
            <form method="POST" action="{{ route('delivery.notify') }}">@csrf<button class="btn pri"><x-icon name="send"/>Kirim info antar ke pelanggan</button></form>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Titik antar" :value="$stats['stops']">{{ $routes->count() }} rute · {{ $stats['couriers'] }} kurir</x-kpi>
        <x-kpi label="Terkirim" :value="$stats['delivered']">{{ $stats['stops'] ? round($stats['delivered'] / $stats['stops'] * 100) : 0 }}% selesai</x-kpi>
        <x-kpi label="Terlambat" :value="$stats['late'].' rute'">{{ $stats['late_reason'] ?? 'Semua sesuai jadwal' }}</x-kpi>
        <x-kpi label="Titik rantangan" :value="$rantangCount">Rantang dijemput saat antar berikutnya</x-kpi>
    </div>

    @if ($routes->isEmpty())
        <div class="card empty">Belum ada rute untuk tanggal ini.</div>
    @else
        <div class="g12">
            <div class="card"><h2>Rute hari ini</h2>
                @foreach ($routes as $x)
                    <a class="opt" aria-pressed="{{ $x->is($selected) ? 'true' : 'false' }}" href="{{ route('delivery.index', ['rute' => $x->id]) }}" style="flex-direction:column;align-items:stretch;gap:8px">
                        <span class="between"><span class="row"><span class="dot" style="background:{{ $x->color }};width:10px;height:10px"></span><b>{{ $x->area }}</b></span><x-badge :tone="$x->state[1]">{{ $x->state[0] }}</x-badge></span>
                        <span class="between sub"><span>Kurir: {{ $x->courier?->name ?? '—' }}</span><span>{{ $x->delivered_count }}/{{ $x->stops_count }} titik</span></span>
                        <span class="bar" style="height:6px"><i style="width:{{ $x->progress_percent }}%;background:{{ $x->color }}"></i></span>
                    </a>
                @endforeach
            </div>
            <div class="card">
                <div class="between">
                    <h2>{{ $selected->area }}</h2>
                    <form class="row" method="POST" action="{{ route('delivery.assign', $selected) }}">
                        @csrf @method('PATCH')
                        <label for="kurirSel" class="sub">Kurir</label>
                        <select id="kurirSel" name="courier_id" style="width:160px;height:36px" data-autosubmit>
                            @foreach ($couriers as $k)<option value="{{ $k->id }}" @selected($k->id === $selected->courier_id)>{{ $k->name }}</option>@endforeach
                        </select>
                    </form>
                </div>
                <div class="map">
                    <svg viewBox="0 0 100 90" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M0 30 Q30 40 50 20 T100 25" stroke="#fff" stroke-width="3" fill="none"/><path d="M10 90 Q20 60 45 55 T95 40" stroke="#fff" stroke-width="4" fill="none"/><path d="M60 0 L55 90" stroke="#fff" stroke-width="2.5" fill="none"/><path d="M0 70 L100 80" stroke="#fff" stroke-width="2" fill="none"/>
                        @foreach ($routes as $x)
                            @php $on = $x->is($selected); $pts = $x->map_points ?? []; @endphp
                            <polyline points="{{ collect($pts)->map(fn ($p) => implode(',', $p))->implode(' ') }}" fill="none" stroke="{{ $x->color }}" opacity="{{ $on ? 1 : .35 }}" stroke-linejoin="round" vector-effect="non-scaling-stroke" style="stroke-width:{{ $on ? 4 : 2 }}px"/>
                            @foreach (array_slice($pts, 1) as $p)
                                <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="{{ $on ? 1.6 : 1 }}" fill="{{ $x->color }}" opacity="{{ $on ? 1 : .4 }}"/>
                            @endforeach
                        @endforeach
                        <rect x="15.5" y="59.5" width="5" height="5" rx="1" fill="#1C2620"/>
                    </svg>
                    <span class="lbl">Peta rute · integrasi Google Maps · ■ dapur HC</span>
                </div>
                <div>
                    @php
                        $lastDone = $selected->stops->whereNotNull('delivered_at')->sortBy('delivered_at')->last();
                        $shown = collect([$lastDone])->filter()->concat($selected->stops->whereNull('delivered_at')->take(4));
                    @endphp
                    @foreach ($shown as $s)
                        <div class="list-item">
                            <x-badge :tone="$s->delivered_at ? 'ok' : 'gray'" style="width:28px;justify-content:center;padding:3px 0">{{ $s->sequence }}</x-badge>
                            <span class="grow">{{ $s->name }} · <span class="muted">{{ $s->address }}</span></span>
                            <span class="sub">{{ $s->delivered_at ? 'Terkirim '.$s->delivered_at->format('H.i').($s->proof_photo ? ' · foto ✓' : '') : 'Estimasi '.($s->eta ?? '—') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
