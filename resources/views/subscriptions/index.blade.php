@extends('layouts.app')
@section('title', 'Langganan Rantangan')

@section('content')
    <x-page-head eyebrow="Paket harian, mingguan & bulanan ·" title="Langganan Rantangan">
        <x-slot:actions>
            <form method="POST" action="{{ route('subscriptions.remind-all') }}">@csrf<button class="btn pri"><x-icon name="send"/>Ingatkan yang hampir habis</button></form>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Langganan aktif" :value="$active"><span class="delta up">▲ {{ $newThisMonth }}</span> bulan ini</x-kpi>
        <x-kpi label="Habis ≤ 3 hari" :value="$expiring">Potensi perpanjang</x-kpi>
        <x-kpi label="Tingkat perpanjang" :value="$renewRate.'%'">Pelanggan &gt; 1 bulan</x-kpi>
        <x-kpi label="Dijeda" :value="$paused">Libur / keluar kota</x-kpi>
    </div>

    <div class="card pad0">
        <div class="between" style="padding:14px 16px;flex-wrap:wrap">
            <div class="chips">
                @foreach (\App\Http\Controllers\SubscriptionController::FILTERS as $f)
                    <a class="chip" aria-pressed="{{ $f === $filter ? 'true' : 'false' }}" href="{{ route('subscriptions.index', $f === 'Semua' ? [] : ['filter' => $f]) }}">{{ $f }}</a>
                @endforeach
            </div>
            <span class="sub">Menampilkan {{ $list->count() }} dari {{ $total }}</span>
        </div>
        <div class="tablewrap"><table>
            <thead><tr><th>Pelanggan</th><th>Paket</th><th class="num">Porsi</th><th style="width:180px">Sisa hari</th><th>Preferensi</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($list as $s)
                    @php [$label, $tone] = $s->state; @endphp
                    <tr>
                        <td><div class="col"><b>{{ $s->customer->name }}</b><span class="sub">{{ $s->customer->area }}</span></div></td>
                        <td>{{ $s->package_label }}</td>
                        <td class="num">{{ $s->portions }}</td>
                        <td><div class="col" style="gap:5px"><span class="sub"><b style="color:var(--ink)">{{ $s->remaining_days }}</b> dari {{ $s->total_days }} hari</span>
                            <div class="bar" style="height:6px"><i style="width:{{ $s->total_days ? $s->remaining_days / $s->total_days * 100 : 0 }}%;background:{{ $tone === 'bad' ? 'var(--red)' : 'var(--green)' }}"></i></div></div></td>
                        <td class="muted">{{ $s->preference ?: '—' }}</td>
                        <td><x-badge :tone="$tone">{{ $label }}</x-badge></td>
                        <td><div class="row" style="justify-content:flex-end">
                            @if (in_array($tone, ['wait', 'bad'], true))
                                <form method="POST" action="{{ route('subscriptions.remind', $s) }}">@csrf<button class="btn sm">Ingatkan</button></form>
                            @endif
                            @if ($s->isPaused())
                                <form method="POST" action="{{ route('subscriptions.resume', $s) }}">@csrf<button class="btn sm ghost">Lanjutkan</button></form>
                            @else
                                <form method="POST" action="{{ route('subscriptions.pause', $s) }}">@csrf<button class="btn sm ghost"><x-icon name="pause"/>Jeda</button></form>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Tidak ada langganan</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
@endsection
