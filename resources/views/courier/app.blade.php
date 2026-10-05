@extends('layouts.mobile')
@section('title', 'Kurir · '.config('catering.name'))

@section('content')
    @if (! $route)
        <div class="p-hero"><small>Kurir · {{ auth()->user()->name }}</small><h2>Belum ada rute</h2></div>
        <div class="p-body"><div class="card">Belum ada rute yang ditugaskan untuk {{ date_id($date, true) }}.</div></div>
    @else
        <div class="p-hero" style="gap:10px">
            <small>Kurir · {{ $route->courier?->name ?? '—' }} · {{ day_id($date) }}, {{ date_id($date) }}</small>
            <h2>Rute {{ $route->area }}</h2>
            <div class="between" style="font-size:13px"><span>{{ $route->delivered_count }} dari {{ $route->stops_count }} titik terkirim</span><b>{{ $route->progress_percent }}%</b></div>
            <div class="prog"><i style="width:{{ $route->progress_percent }}%"></i></div>
        </div>
        <div class="p-body" style="gap:12px">
            @if ($next)
                <div class="card" style="border:2px solid var(--green);gap:10px;padding:16px">
                    <div class="between"><x-badge tone="ok">Berikutnya · titik {{ $next->sequence }}</x-badge><span class="sub strong">{{ $next->eta ? 'Estimasi '.$next->eta : '' }}</span></div>
                    <b style="font-size:17px">{{ $next->name }}</b><span class="muted">{{ $next->address }}</span>
                    <div class="chips">@foreach ($next->tags ?? [] as $t)<x-badge>{{ $t }}</x-badge>@endforeach</div>
                    @if ($next->note)<span style="font-size:13px"><b>Catatan:</b> {{ $next->note }}</span>@endif
                    <div class="row">
                        <a class="btn grow" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($next->address) }}" target="_blank" rel="noopener">Navigasi</a>
                        @if ($next->order?->customer?->whatsapp)
                            <a class="btn grow" href="tel:{{ preg_replace('/\D/', '', $next->order->customer->whatsapp) }}">Hubungi</a>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('courier.deliver', $next) }}" enctype="multipart/form-data" class="col" style="gap:8px">
                        @csrf
                        <label class="upload">Foto bukti (opsional) <input type="file" name="photo" accept="image/*" capture="environment"></label>
                        <button class="btn pri big" type="submit">Tandai terkirim + foto bukti</button>
                    </form>
                </div>
                @if ($upcoming->isNotEmpty())
                    <h2 style="font-size:15px">Titik selanjutnya</h2>
                    @foreach ($upcoming as $x)
                        <div class="stop"><span class="n">{{ $x->sequence }}</span>
                            <div class="col grow"><b style="font-size:14px">{{ $x->name }}</b><span class="sub">{{ $x->address }} · {{ ($x->tags ?? ['—'])[0] }}</span></div>
                            <x-badge :tone="$x->collect_payment ? 'wait' : 'ok'">{{ $x->payment_label }}</x-badge>
                        </div>
                    @endforeach
                @endif
            @else
                <div class="card" style="text-align:center;gap:8px"><b style="font-size:17px">Semua titik di daftar selesai 🎉</b><span class="muted">Kembali ke dapur, bawa rantang kosong.</span></div>
            @endif
        </div>
    @endif
@endsection
