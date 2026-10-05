@extends('layouts.app')
@section('title', 'Tagihan & Piutang')

@section('content')
    <x-page-head eyebrow="Invoice kontrak, DP & pelunasan event, pembayaran QRIS ·" title="Tagihan & Piutang">
        <x-slot:actions>
            <a class="btn" href="{{ route('invoices.export') }}"><x-icon name="download"/>Unduh laporan</a>
            <form method="POST" action="{{ route('invoices.remind') }}">@csrf<button class="btn pri" @disabled(! $overdueCount)><x-icon name="send"/>Tagih yang lewat tempo</button></form>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Masuk bulan ini" :value="rupiah_short($monthTotal)">QRIS {{ $qrisPercent }}% · transfer {{ 100 - $qrisPercent }}%</x-kpi>
        <x-kpi label="Piutang berjalan" :value="rupiah_short($outstanding)">{{ $outstandingCount }} invoice</x-kpi>
        <x-kpi label="Lewat jatuh tempo" :value="rupiah_short($overdueAmount)">{{ $overdueCount }} klien{{ $overdueCount ? ' · '.$overdueDays.' hari' : '' }}</x-kpi>
        <x-kpi label="Rata-rata dibayar" :value="$avgDays.' hari'">Setelah invoice terbit</x-kpi>
    </div>

    <div class="g21">
        <div class="card pad0">
            <div style="padding:18px 20px"><h2>Invoice</h2></div>
            <div class="tablewrap"><table>
                <thead><tr><th>No. invoice</th><th>Klien</th><th>Periode</th><th class="num">Jumlah</th><th>Jatuh tempo</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($invoices as $v)
                        <tr>
                            <td class="muted">{{ $v->number }}</td>
                            <td class="strong">{{ $v->client_label }}</td>
                            <td>{{ $v->period }}</td>
                            <td class="num">{{ rupiah($v->amount) }}</td>
                            <td>{{ date_id($v->due_at) }}</td>
                            <td><x-badge :tone="$v->state[1]">{{ $v->state[0] }}</x-badge></td>
                            <td>
                                @unless ($v->paid_at)
                                    <form method="POST" action="{{ route('invoices.paid', $v) }}" data-confirm="Tandai {{ $v->number }} sudah dibayar?">@csrf<button class="btn sm ghost">Lunas</button></form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">Belum ada invoice</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="card"><h2>Pembayaran masuk hari ini</h2>
            @forelse ($todayPayments as $p)
                <div class="list-item"><div class="col grow"><b>{{ $p->customer->name }}</b><span class="sub">{{ $p->method }} · {{ $p->paid_at->format('H.i') }} · {{ $p->description }}</span></div><b>{{ rupiah($p->amount) }}</b></div>
            @empty
                <span class="muted">Belum ada pembayaran hari ini.</span>
            @endforelse
            <div class="note">Pembayaran QRIS tercocokkan otomatis dengan pesanan. Status “Lunas” langsung muncul di dashboard tanpa cek mutasi manual.</div>
        </div>
    </div>
@endsection
