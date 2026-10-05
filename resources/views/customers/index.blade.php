@extends('layouts.app')
@section('title', 'Data Pelanggan')

@section('content')
    <x-page-head eyebrow="Satu profil per pelanggan, dari semua lini ·" title="Data Pelanggan">
        <x-slot:actions>
            <button class="btn" type="button" data-modal="tpl-customer">+ Pelanggan</button>
            <button class="btn pri" type="button" data-modal="tpl-promo"><x-icon name="send"/>Kirim promo WhatsApp</button>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Total pelanggan" :value="$total"><span class="delta up">▲ {{ $newThisMonth }}</span> bulan ini</x-kpi>
        <x-kpi label="Pelanggan kembali" :value="$returningPercent.'%'">Pesan lebih dari sekali</x-kpi>
        <x-kpi label="Rata-rata nilai pesanan" :value="rupiah_short($avgOrder)">Non-kontrak</x-kpi>
        <x-kpi label="Tidak aktif > 60 hari" :value="$inactive">Target kampanye ajak kembali</x-kpi>
    </div>

    <div class="card pad0">
        <div class="between" style="padding:14px 16px;flex-wrap:wrap">
            <div class="chips">
                <a class="chip" aria-pressed="{{ $segment ? 'false' : 'true' }}" href="{{ route('customers.index') }}">Semua</a>
                @foreach (\App\Models\Customer::SEGMENTS as $s)
                    <a class="chip" aria-pressed="{{ $segment === $s ? 'true' : 'false' }}" href="{{ route('customers.index', ['segmen' => $s]) }}">{{ $s }}</a>
                @endforeach
            </div>
        </div>
        <div class="tablewrap"><table>
            <thead><tr><th>Pelanggan</th><th>Segmen</th><th>Area</th><th>Sejak</th><th>Pesanan</th><th>Terakhir</th><th>Preferensi</th><th>Nilai</th></tr></thead>
            <tbody>
                @forelse ($customers as $c)
                    <tr class="click" tabindex="0" data-drawer-url="{{ route('customers.show', $c) }}">
                        <td class="strong">{{ $c->name }}</td>
                        <td>{{ $c->segment }}</td>
                        <td class="muted">{{ $c->area }}</td>
                        <td>{{ $c->customer_since ? month_id($c->customer_since->month).' '.$c->customer_since->year : '—' }}</td>
                        <td>{{ $c->order_summary }}</td>
                        <td>{{ $c->orders_max_delivery_date ? (\Illuminate\Support\Carbon::parse($c->orders_max_delivery_date)->isToday() ? 'Hari ini' : date_id(\Illuminate\Support\Carbon::parse($c->orders_max_delivery_date))) : '—' }}</td>
                        <td class="muted">{{ $c->preference ?: '—' }}</td>
                        <td><x-badge :tone="$c->value_tone">{{ $c->value_tier }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Belum ada pelanggan</td></tr>
                @endforelse
            </tbody>
        </table></div>
        {{ $customers->links() }}
    </div>
@endsection

@push('templates')
    <template id="tpl-customer">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Pelanggan baru</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="cuForm" method="POST" action="{{ route('customers.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field"><label class="l" for="cu-n">Nama</label><input id="cu-n" name="name" type="text" required></div>
                <div class="field"><label class="l" for="cu-s">Segmen</label><select id="cu-s" name="segment">@foreach (\App\Models\Customer::SEGMENTS as $s)<option>{{ $s }}</option>@endforeach</select></div>
                <div class="field"><label class="l" for="cu-w">WhatsApp</label><input id="cu-w" name="whatsapp" type="tel"></div>
                <div class="field"><label class="l" for="cu-a">Area</label><input id="cu-a" name="area" type="text"></div>
                <div class="field full"><label class="l" for="cu-ad">Alamat</label><input id="cu-ad" name="address" type="text"></div>
                <div class="field"><label class="l" for="cu-p">Preferensi</label><input id="cu-p" name="preference" type="text"></div>
                <div class="field"><label class="l" for="cu-b">Ulang tahun</label><input id="cu-b" name="birthday" type="date"></div>
            </div>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="cuForm" type="submit">Simpan</button></div>
    </template>
    <template id="tpl-promo">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Kirim promo WhatsApp</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="prForm" method="POST" action="{{ route('customers.promo') }}" data-confirm="Kirim promo ke pelanggan pada segmen ini?">
            @csrf
            <div class="form-grid">
                <div class="field full"><label class="l" for="pr-s">Segmen</label><select id="pr-s" name="segment"><option value="">Semua pelanggan</option>@foreach (\App\Models\Customer::SEGMENTS as $s)<option>{{ $s }}</option>@endforeach</select></div>
                <div class="field full"><label class="l" for="pr-m">Pesan</label><textarea id="pr-m" name="message" rows="4" required>Promo minggu ini dari HC Catering: perpanjang langganan bulanan, gratis 1 hari! 🍱</textarea></div>
            </div>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="prForm" type="submit">Kirim</button></div>
    </template>
@endpush
