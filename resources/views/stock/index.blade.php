@extends('layouts.app')
@section('title', 'Stok & Belanja')

@section('content')
    <x-page-head eyebrow="Stok berkurang otomatis sesuai resep & porsi yang dimasak ·" title="Stok & Belanja">
        <x-slot:actions>
            <button class="btn" type="button" data-modal="tpl-stock-in">+ Stok masuk</button>
            <form method="POST" action="{{ route('stock.send-list') }}">@csrf<button class="btn pri"><x-icon name="send"/>Kirim daftar belanja</button></form>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Item dipantau" :value="$ingredients->count()">{{ $ingredients->pluck('category')->unique()->implode(', ') }}</x-kpi>
        <x-kpi label="Di bawah minimum" :value="$belowMin">Perlu dibeli hari ini</x-kpi>
        <x-kpi label="Nilai stok" :value="rupiah_short($stockValue)">Dihitung dari harga beli terakhir</x-kpi>
        <x-kpi label="Sisa & terbuang" :value="str_replace('.', ',', $waste).'%'">Target &lt; 3% per minggu</x-kpi>
    </div>

    <div class="g21">
        <div class="card pad0">
            <div class="between" style="padding:18px 20px"><h2>Stok bahan</h2><button class="btn ghost sm" type="button" data-modal="tpl-ingredient">+ Bahan baru</button></div>
            <div class="tablewrap"><table>
                <thead><tr><th>Bahan</th><th>Kategori</th><th class="num">Stok</th><th class="num">Butuh besok</th><th style="width:140px">Stok vs minimum</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($ingredients as $s)
                        <tr>
                            <td class="strong">{{ $s->name }}</td>
                            <td class="muted">{{ $s->category }}</td>
                            <td class="num">{{ qty($s->stock) }} {{ $s->unit }}</td>
                            <td class="num">{{ qty($s->need_tomorrow) }} {{ $s->unit }}</td>
                            <td><div class="bar" style="height:8px"><i style="width:{{ $s->level_percent }}%;background:{{ $s->isBelowMinimum() ? 'var(--red)' : 'var(--green)' }}"></i></div></td>
                            <td><x-badge :tone="$s->state[1]">{{ $s->state[0] }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
        <div class="card">
            <div class="between"><h2>Daftar belanja · {{ date_id(today()->addDay()) }}</h2><span class="sub">{{ $shopping->count() }} item</span></div>
            @forelse ($shopping as $s)
                <label class="check" style="border-bottom:1px solid var(--line2);padding-bottom:8px"><input type="checkbox">
                    <span class="col grow"><b>{{ $s->name }} · {{ qty($s->purchase_qty) }} {{ $s->unit }}</b><span class="sub">{{ $s->supplier }} · {{ rupiah($s->last_price) }}/{{ $s->unit }}</span></span>
                </label>
            @empty
                <span class="muted">Semua bahan cukup untuk besok.</span>
            @endforelse
            <div class="kv total"><span>Estimasi belanja</span><span>{{ rupiah($estimate) }}</span></div>
            <form method="POST" action="{{ route('stock.po') }}">@csrf<button class="btn pri" style="width:100%" @disabled($shopping->isEmpty())>Buat PO ke pemasok</button></form>
        </div>
    </div>
@endsection

@push('templates')
    <template id="tpl-stock-in">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Catat stok</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="siForm" method="POST" action="{{ route('stock.in') }}">
            @csrf
            <div class="form-grid">
                <div class="field full"><label class="l" for="si-i">Bahan</label>
                    <select id="si-i" name="ingredient_id">@foreach ($ingredients as $i)<option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>@endforeach</select></div>
                <div class="field"><label class="l" for="si-t">Jenis</label>
                    <select id="si-t" name="type"><option value="in">Stok masuk</option><option value="out">Pemakaian</option><option value="waste">Terbuang</option></select></div>
                <div class="field"><label class="l" for="si-q">Jumlah</label><input id="si-q" name="quantity" type="number" step="0.01" min="0.01" required></div>
                <div class="field"><label class="l" for="si-p">Harga per satuan (opsional)</label><input id="si-p" name="unit_price" type="number" min="0"></div>
                <div class="field"><label class="l" for="si-n">Catatan</label><input id="si-n" name="note" type="text" placeholder="Contoh: dari Pasar induk"></div>
            </div>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="siForm" type="submit">Simpan</button></div>
    </template>
    <template id="tpl-ingredient">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Bahan baru</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="ingForm" method="POST" action="{{ route('stock.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field full"><label class="l" for="ing-n">Nama bahan</label><input id="ing-n" name="name" type="text" required></div>
                <div class="field"><label class="l" for="ing-c">Kategori</label><select id="ing-c" name="category">@foreach (\App\Models\Ingredient::CATEGORIES as $c)<option>{{ $c }}</option>@endforeach</select></div>
                <div class="field"><label class="l" for="ing-u">Satuan</label><input id="ing-u" name="unit" type="text" value="kg" required></div>
                <div class="field"><label class="l" for="ing-s">Stok awal</label><input id="ing-s" name="stock" type="number" step="0.01" min="0" value="0" required></div>
                <div class="field"><label class="l" for="ing-m">Stok minimum</label><input id="ing-m" name="min_stock" type="number" step="0.01" min="0" value="0" required></div>
                <div class="field"><label class="l" for="ing-sp">Pemasok</label><input id="ing-sp" name="supplier" type="text"></div>
                <div class="field"><label class="l" for="ing-p">Harga beli per satuan</label><input id="ing-p" name="last_price" type="number" min="0"></div>
            </div>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="ingForm" type="submit">Simpan</button></div>
    </template>
@endpush
