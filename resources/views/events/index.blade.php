@extends('layouts.app')
@section('title', 'Event & Prasmanan')

@section('content')
    <x-page-head eyebrow="Prasmanan, nasi box acara, tumpeng & gubukan ·" title="Event & Prasmanan">
        <x-slot:actions><button class="btn pri" type="button" data-modal="tpl-event"><x-icon name="plus"/>Permintaan baru</button></x-slot:actions>
    </x-page-head>

    <div class="kanban">
        @foreach ($columns as $i => $col)
            <div class="kcol">
                <h3><span>{{ $col }}</span><span class="sub">{{ ($events[$i] ?? collect())->count() }}</span></h3>
                @foreach ($events[$i] ?? [] as $e)
                    <a class="kcard" aria-pressed="{{ $e->is($selected) ? 'true' : 'false' }}" href="{{ route('events.index', ['event' => $e->id]) }}">
                        <b>{{ $e->name }}</b><span class="sub">{{ $e->client_label }}</span>
                        <span class="between"><x-badge>{{ $e->pax }} pax</x-badge><span class="sub strong">{{ date_id($e->event_date) }}</span></span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>

    @if ($selected)
        <div class="g21">
            <div class="card">
                <div class="between" style="flex-wrap:wrap">
                    <div class="col"><span class="sub">{{ $selected->stage_label }} · {{ date_id($selected->event_date) }}{{ $selected->event_time ? ' · '.$selected->event_time : '' }}</span><h2 style="font-size:20px">{{ $selected->name }} · {{ $selected->client_label }}</h2></div>
                    @if ($selected->stage < count($columns) - 1)
                        <form method="POST" action="{{ route('events.advance', $selected) }}">@csrf<button class="btn pri">Pindah ke “{{ $columns[$selected->stage + 1] }}”</button></form>
                    @else
                        <x-badge tone="ok">Selesai</x-badge>
                    @endif
                </div>
                <div class="g2">
                    <div><h3 style="margin-bottom:8px">Menu prasmanan</h3>
                        @foreach ($selected->menu ?? [] as $m)
                            <div class="row" style="padding:5px 0"><span class="dot" style="background:var(--brand)"></span>{{ $m }}</div>
                        @endforeach
                    </div>
                    <div><h3 style="margin-bottom:8px">Rincian</h3>
                        <div class="kv"><span class="muted">Jumlah tamu</span><b>{{ $selected->pax }} pax</b></div>
                        <div class="kv"><span class="muted">Harga per pax</span><b>{{ rupiah($selected->price_per_pax) }}</b></div>
                        <div class="kv"><span class="muted">Sewa peralatan</span><b>{{ rupiah($selected->equipment_cost) }}</b></div>
                        <div class="kv"><span class="muted">DP 50%</span><b><x-badge :tone="$selected->dp_received ? 'ok' : 'wait'">{{ $selected->dp_received ? 'Diterima' : 'Belum' }}</x-badge></b></div>
                        <div class="kv total"><span>Total</span><span>{{ rupiah($selected->total) }}</span></div>
                        <form method="POST" action="{{ route('events.quotation', $selected) }}">@csrf<button class="btn" style="margin-top:10px;width:100%"><x-icon name="send"/>Kirim quotation</button></form>
                    </div>
                </div>
            </div>
            <div class="card"><h2>Checklist persiapan</h2>
                @foreach ($selected->checklist as $c)
                    <form class="check-form" method="POST" action="{{ route('events.checklist', [$selected, $c]) }}">
                        @csrf @method('PATCH')
                        <button type="submit" aria-pressed="{{ $c->done ? 'true' : 'false' }}"><span class="box {{ $c->done ? 'on' : '' }}">@if ($c->done)<x-icon name="check" size="14" stroke-width="3"/>@endif</span>{{ $c->label }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif
@endsection

@push('templates')
    <template id="tpl-event">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Permintaan event baru</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="evForm" method="POST" action="{{ route('events.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field full"><label class="l" for="ev-n">Nama acara</label><input id="ev-n" name="name" type="text" placeholder="Contoh: Syukuran kantor" required></div>
                <div class="field"><label class="l" for="ev-c">Klien</label><input id="ev-c" name="customer" type="text" list="custs-ev" required>
                    <datalist id="custs-ev">@foreach ($customerNames as $name)<option value="{{ $name }}">@endforeach</datalist></div>
                <div class="field"><label class="l" for="ev-a">Lokasi / area</label><input id="ev-a" name="venue_area" type="text"></div>
                <div class="field"><label class="l" for="ev-p">Jumlah tamu (pax)</label><input id="ev-p" name="pax" type="number" min="10" value="100" required></div>
                <div class="field"><label class="l" for="ev-h">Harga per pax</label><input id="ev-h" name="price_per_pax" type="number" min="0" value="75000"></div>
                <div class="field"><label class="l" for="ev-d">Tanggal acara</label><input id="ev-d" name="event_date" type="date" value="{{ today()->addWeeks(2)->toDateString() }}" required></div>
                <div class="field"><label class="l" for="ev-t">Jam</label><input id="ev-t" name="event_time" type="text" value="12.00"></div>
            </div>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="evForm" type="submit">Simpan permintaan</button></div>
    </template>
@endpush
