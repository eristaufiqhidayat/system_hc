@extends('layouts.app')
@section('title', 'Produksi Dapur')

@section('content')
    <x-page-head eyebrow="Otomatis dari pesanan terkonfirmasi · ditutup pukul 20.00 ·" :title="'Rekap Produksi · '.day_id($date).', '.date_id($date)">
        <x-slot:actions>
            <form method="POST" action="{{ route('production.generate') }}" data-confirm="Susun ulang rekap besok dari pesanan terbaru? Status masak akan direset.">@csrf<button class="btn"><x-icon name="repeat"/>Susun ulang</button></form>
            <a class="btn" href="{{ route('production.labels') }}" target="_blank"><x-icon name="print"/>Cetak label</a>
            <a class="btn pri" href="{{ route('stock.index') }}">Buat daftar belanja</a>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Total porsi" :value="qty($total)">
            @if ($growth !== null){{ $growth >= 0 ? 'Naik' : 'Turun' }} {{ abs($growth) }}% dari minggu lalu @else Dari kontrak, langganan & pesanan @endif
        </x-kpi>
        <x-kpi label="Menu dimasak" :value="$summary['menus']">{{ $hospitalMenus }} menu khusus RS</x-kpi>
        <x-kpi label="Porsi diet khusus" :value="qty($summary['special_diet'])">Rendah garam, lunak, cair, DM</x-kpi>
        <x-kpi label="Bahan kurang" :value="$shortages">Beli sebelum 17.00</x-kpi>
    </div>

    <div class="card pad0">
        <div style="padding:18px 20px"><h2>Daftar masak per menu</h2></div>
        <div class="tablewrap"><table>
            <thead><tr><th>Menu</th><th class="num">Total</th><th class="num">Kantor</th><th class="num">Rantang</th><th class="num">RS</th><th class="num">Event</th><th>Variasi diet</th><th>Penanggung jawab</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($items as $m)
                    <tr>
                        <td class="strong">{{ $m->menu_name }}</td>
                        <td class="num strong" style="font-size:16px">{{ qty($m->total) }}</td>
                        <td class="num">{{ $m->office_portions ?: '—' }}</td>
                        <td class="num">{{ $m->rantang_portions ?: '—' }}</td>
                        <td class="num">{{ $m->hospital_portions ?: '—' }}</td>
                        <td class="num">{{ $m->event_portions ?: '—' }}</td>
                        <td class="muted">{{ $m->diet_notes ?: '—' }}</td>
                        <td>{{ $m->cook }}</td>
                        <td>
                            <form method="POST" action="{{ route('production.update', $m) }}">
                                @csrf @method('PATCH')
                                <label class="sr-only" for="st-{{ $m->id }}">Status {{ $m->menu_name }}</label>
                                <select id="st-{{ $m->id }}" name="status" class="sm badge b-{{ $m->status_tone }}" style="border:0" data-autosubmit>
                                    @foreach (array_keys(\App\Models\ProductionItem::STATUSES) as $s)<option @selected($s === $m->status)>{{ $s }}</option>@endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty">Belum ada rekap untuk tanggal ini</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="g21">
        <div class="card"><h2>Pembagian packing per tujuan</h2>
            @foreach ($packing as [$dest, $detail, $time])
                <div class="list-item"><div class="col grow"><b>{{ $dest }}</b><span class="sub">{{ $detail }}</span></div><x-badge>{{ $time }}</x-badge></div>
            @endforeach
        </div>
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="card"><h2>Jadwal dapur</h2>
                @foreach ([['04.00', 'Persiapan bahan & masak lauk utama'], ['06.00', 'Packing RS shift pagi'], ['09.30', 'Packing kantor & rantangan per rute'], ['10.30', 'Kurir berangkat · '.\App\Models\DeliveryRoute::whereDate('route_date', today())->count().' rute'], ['15.00', 'Packing RS shift malam'], ['20.00', 'Rekap besok dikunci']] as [$t, $j])
                    <div class="row" style="align-items:baseline"><b style="width:52px;color:var(--green)">{{ $t }}</b><span>{{ $j }}</span></div>
                @endforeach
            </div>
            @if ($labelSample && ($diet = $labelSample->diets->first()))
                <div class="card" style="border:1px dashed #D9A15C;background:#FFFAF1;gap:4px">
                    <span class="sub strong" style="color:var(--amber-ink);text-transform:uppercase;letter-spacing:.06em">Contoh label kemasan</span>
                    <b style="font-size:18px">{{ $labelSample->customer->name }} · Kamar {{ $diet->room }} · Siang</b>
                    <span>{{ $items->where('hospital_portions', '>', 0)->pluck('menu_name')->take(3)->implode(' · ') }}</span>
                    <b style="color:var(--red-ink)">{{ mb_strtoupper(str_starts_with($diet->diet_type, 'Diet') ? $diet->diet_type : 'Diet '.$diet->diet_type) }}{{ $diet->note ? ' · '.mb_strtolower($diet->note) : '' }}</b>
                </div>
            @endif
        </div>
    </div>
@endsection
