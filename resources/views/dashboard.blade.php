@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <x-page-head :eyebrow="date_long_id(today())" title="Selamat {{ now()->hour < 11 ? 'pagi' : (now()->hour < 15 ? 'siang' : (now()->hour < 18 ? 'sore' : 'malam')) }}, Tim HC">
        <x-slot:actions>
            @if (auth()->user()->canAccess('production'))
                <a class="btn" href="{{ route('production.index') }}"><x-icon name="print"/>Rekap dapur</a>
            @endif
            @if (auth()->user()->canAccess('orders'))
                <button class="btn pri" type="button" data-modal="tpl-new-order"><x-icon name="plus"/>Pesanan baru</button>
            @endif
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Total porsi besok" :value="qty($portionsTomorrow)">{{ count($perLine) }} lini bisnis · {{ $activeOrders }} pesanan aktif</x-kpi>
        <x-kpi label="Pengiriman hari ini" :value="$deliveryStats['delivered'].' / '.$deliveryStats['stops']">{{ $deliveryStats['stops'] - $deliveryStats['delivered'] }} titik masih di jalan</x-kpi>
        <x-kpi label="Omzet bulan ini" :value="rupiah_short($revenue)"><x-delta :value="$revenueDelta"/> dari {{ month_id(now()->subMonthNoOverflow()->month, false) }}</x-kpi>
        <x-kpi label="Piutang jatuh tempo" :value="rupiah_short($overdueAmount)">{{ $overdueCount }} invoice lewat tempo</x-kpi>
    </div>

    <div class="g3">
        <div class="card">
            <div class="between"><h2>Porsi besok per lini</h2><a class="btn ghost sm" href="{{ route('production.index') }}">Detail</a></div>
            <x-bar-chart :rows="$perLine" unit=" porsi"/>
        </div>
        <div class="card">
            <div class="between"><h2>Pengiriman siang ini</h2><a class="btn ghost sm" href="{{ route('delivery.index') }}">Peta</a></div>
            <div>
                @forelse ($routes as $r)
                    <div class="list-item">
                        <div class="col grow"><b>{{ $r->area }}</b><span class="sub">{{ $r->courier?->name ?? 'Belum ada kurir' }} · {{ $r->stops_count }} titik</span></div>
                        <b>{{ $r->delivered_count }}/{{ $r->stops_count }}</b>
                        <x-badge :tone="$r->state[1]">{{ $r->state[0] }}</x-badge>
                    </div>
                @empty
                    <div class="empty">Belum ada rute hari ini</div>
                @endforelse
            </div>
        </div>
        <div class="card warn">
            <h2>Perlu perhatian</h2>
            @forelse ($attention as $a)
                <a href="{{ route($a['route']) }}" style="display:flex;gap:12px;align-items:flex-start;color:inherit;text-decoration:none">
                    <span class="dot" style="background:var(--red);margin-top:7px"></span>
                    <span class="col"><b>{{ $a['title'] }}</b><span class="sub">{{ $a['detail'] }}</span></span>
                </a>
            @empty
                <span class="muted">Semua aman. Tidak ada yang perlu ditindaklanjuti.</span>
            @endforelse
        </div>
    </div>

    <div class="card pad0">
        <div class="between" style="padding:18px 20px"><h2>Pesanan masuk terbaru</h2><a class="btn ghost sm" href="{{ route('orders.index') }}">Lihat semua</a></div>
        <div class="tablewrap">@include('partials.order-table', ['orders' => $latestOrders])</div>
    </div>
@endsection
