@extends('layouts.app')
@section('title', 'Kontrak Kantor & RS')

@section('content')
    <x-page-head eyebrow="Klien B2B dengan pesanan rutin & invoice bulanan ·" title="Kontrak Kantor & Rumah Sakit">
        <x-slot:actions><button class="btn" type="button" data-modal="tpl-contract">+ Kontrak baru</button></x-slot:actions>
    </x-page-head>

    @if (! $selected)
        <div class="card empty">Belum ada kontrak.</div>
    @else
        <div class="g12" style="align-items:start">
            <div class="card"><h2>Klien kontrak · {{ $contracts->count() }}</h2>
                @foreach ($contracts as $x)
                    <a class="opt" aria-pressed="{{ $x->is($selected) ? 'true' : 'false' }}" href="{{ route('contracts.index', ['klien' => $x->id]) }}" style="flex-direction:column;align-items:stretch;gap:3px">
                        <span class="between"><b>{{ $x->display_name }}</b><span class="sub strong">{{ $x->type }}</span></span>
                        <span class="sub">{{ $x->summary }}</span>
                    </a>
                @endforeach
            </div>
            <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
                <div class="card">
                    <div class="between" style="flex-wrap:wrap">
                        <div class="col"><span class="sub">{{ $selected->type }} · kontrak s/d {{ month_id($selected->ends_at->month) }} {{ $selected->ends_at->year }}</span><h2 style="font-size:22px">{{ $selected->display_name }}</h2></div>
                        @if ($invoice)
                            <x-badge tone="ok">Invoice {{ month_id($month->month, false) }} terbit · {{ $invoice->number }}</x-badge>
                        @elseif ($recap['portions'] > 0)
                            <form method="POST" action="{{ route('contracts.invoice', $selected) }}" data-confirm="Terbitkan invoice {{ month_id($month->month, false) }} untuk {{ $selected->customer->name }}?">
                                @csrf<button class="btn pri">Terbitkan invoice {{ month_id($month->month, false) }}</button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="g3">
                    @foreach ($selected->shifts as $s)
                        <x-kpi :label="$s->label" :value="$s->value">{{ $s->note }}</x-kpi>
                    @endforeach
                </div>
                @if ($selected->hasDietTracking())
                    <div class="card pad0">
                        <div class="between" style="padding:18px 20px"><h2>Diet khusus pasien · dari ahli gizi</h2><button class="btn sm" type="button" data-modal="tpl-diet">+ Ubah diet</button></div>
                        <div class="tablewrap"><table>
                            <thead><tr><th>Kamar</th><th>Jenis diet</th><th>Catatan</th><th>Berlaku</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($selected->diets as $d)
                                    <tr><td class="strong">{{ $d->room }}</td><td>{{ $d->diet_type }}</td><td class="muted">{{ $d->note ?: '—' }}</td><td>{{ $d->valid_label }}</td><td><x-badge :tone="$d->is_new ? 'wait' : 'ok'">{{ $d->is_new ? 'Baru' : 'Aktif' }}</x-badge></td></tr>
                                @empty
                                    <tr><td colspan="5" class="empty">Belum ada diet khusus</td></tr>
                                @endforelse
                            </tbody>
                        </table></div>
                    </div>
                @endif
                <div class="g2">
                    <div class="card"><h2>Rekap tagihan {{ month_id($month->month, false) }}</h2>
                        <div>
                            <div class="kv"><span class="muted">Porsi terkirim</span><b>{{ qty($recap['portions']) }}</b></div>
                            <div class="kv"><span class="muted">Harga per porsi</span><b>{{ rupiah($selected->price_per_portion) }}</b></div>
                            <div class="kv"><span class="muted">Porsi tambahan</span><b>{{ qty($recap['extra']) }}</b></div>
                            <div class="kv"><span class="muted">Total tagihan</span><b>{{ rupiah($recap['amount']) }}</b></div>
                            <div class="kv total"><span>Jatuh tempo</span><span style="color:var(--red-ink)">{{ $invoice ? date_id($invoice->due_at, true) : date_id(today()->addDays($selected->payment_term_days), true) }}</span></div>
                        </div>
                    </div>
                    <div class="card"><h2>Kontak & ketentuan</h2>
                        <div class="col" style="gap:8px">
                            <span><b>PIC:</b> {{ $selected->pic ?: '—' }}</span>
                            <span><b>Antar:</b> {{ $selected->delivery_info ?: '—' }}</span>
                            <span><b>Perubahan:</b> paling lambat H-1 pukul 19.00</span>
                            <span><b>Bayar:</b> invoice bulanan, termin {{ $selected->payment_term_days }} hari</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('templates')
    <template id="tpl-contract">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Kontrak baru</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="ctForm" method="POST" action="{{ route('contracts.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field"><label class="l" for="ct-n">Nama klien</label><input id="ct-n" name="name" type="text" required></div>
                <div class="field"><label class="l" for="ct-a">Area</label><input id="ct-a" name="area" type="text" required></div>
                <div class="field"><label class="l" for="ct-t">Jenis</label><select id="ct-t" name="type">@foreach (\App\Models\Contract::TYPES as $t)<option>{{ $t }}</option>@endforeach</select></div>
                <div class="field"><label class="l" for="ct-d">Porsi per hari</label><input id="ct-d" name="daily_portions" type="number" min="0" required></div>
                <div class="field"><label class="l" for="ct-p">Harga per porsi</label><input id="ct-p" name="price_per_portion" type="number" min="0" required></div>
                <div class="field"><label class="l" for="ct-pic">PIC & nomor</label><input id="ct-pic" name="pic" type="text"></div>
                <div class="field full"><label class="l" for="ct-dl">Jam & lokasi antar</label><input id="ct-dl" name="delivery_info" type="text" placeholder="11.30, lobi lt. 1"></div>
                <div class="field"><label class="l" for="ct-s">Mulai</label><input id="ct-s" name="starts_at" type="date" value="{{ today()->toDateString() }}" required></div>
                <div class="field"><label class="l" for="ct-e">Berakhir</label><input id="ct-e" name="ends_at" type="date" value="{{ today()->endOfYear()->toDateString() }}" required></div>
            </div>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="ctForm" type="submit">Simpan kontrak</button></div>
    </template>
    @if ($selected)
        <template id="tpl-diet">
            <div class="mh"><h2 id="modalTitle" style="font-size:18px">Ubah diet · {{ $selected->customer->name }}</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
            <form class="mb" id="dtForm" method="POST" action="{{ route('contracts.diet', $selected) }}">
                @csrf
                <div class="form-grid">
                    <div class="field"><label class="l" for="dt-r">Kamar</label><input id="dt-r" name="room" type="text" required></div>
                    <div class="field"><label class="l" for="dt-t">Jenis diet</label><input id="dt-t" name="diet_type" type="text" list="diet-types" required>
                        <datalist id="diet-types">@foreach (\App\Models\PatientDiet::TYPES as $t)<option value="{{ $t }}">@endforeach</datalist></div>
                    <div class="field full"><label class="l" for="dt-n">Catatan</label><input id="dt-n" name="note" type="text" placeholder="Contoh: tanpa kacang (alergi)"></div>
                    <div class="field"><label class="l" for="dt-v">Berlaku sampai (kosongkan bila tetap)</label><input id="dt-v" name="valid_until" type="date"></div>
                </div>
            </form>
            <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="dtForm" type="submit">Simpan diet</button></div>
        </template>
    @endif
@endpush
