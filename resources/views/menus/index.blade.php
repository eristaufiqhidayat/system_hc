@extends('layouts.app')
@section('title', 'Menu & Resep')

@section('content')
    <x-page-head eyebrow="Siklus menu 4 minggu untuk rantangan & kantor ·" title="Menu & Resep">
        <x-slot:actions>
            <form method="POST" action="{{ route('menus.announce') }}">@csrf<button class="btn"><x-icon name="send"/>Umumkan menu</button></form>
            <button class="btn pri" type="button" data-modal="tpl-menu"><x-icon name="plus"/>Menu baru</button>
        </x-slot:actions>
    </x-page-head>

    <div class="card">
        <div class="between"><h2>Rotasi menu utama</h2><span class="sub">Pilih menu untuk melihat resep & HPP</span></div>
        <div class="rot">
            <span></span>
            @foreach (\App\Models\MenuRotation::WEEKDAYS as $d)<span class="h">{{ $d }}</span>@endforeach
            @foreach (range(1, 4) as $w)
                <span class="wk">Minggu {{ $w }}</span>
                @foreach (array_keys(\App\Models\MenuRotation::WEEKDAYS) as $d)
                    @php $cell = $grid->get($w)?->get($d); @endphp
                    <a class="cell" aria-pressed="{{ $w === $week && $d === $day ? 'true' : 'false' }}" href="{{ route('menus.index', ['sel' => $w.','.$d]) }}">{{ $cell?->menu->name ?? '—' }}</a>
                @endforeach
            @endforeach
        </div>
    </div>

    <div class="g21">
        @if ($menu)
            <div class="card pad0">
                <div class="between" style="padding:18px 20px">
                    <div class="col"><span class="sub">Resep standar · per 1 porsi</span><h2 style="font-size:20px">{{ $menu->name }}</h2></div>
                    <x-badge :tone="$menu->recipe_locked ? 'ok' : 'wait'">{{ $menu->recipe_locked ? 'Resep terkunci' : 'Draf' }}</x-badge>
                </div>
                <table><thead><tr><th>Bahan</th><th class="num">Takaran</th><th class="num">Biaya</th></tr></thead><tbody>
                    @forelse ($menu->recipeItems as $b)
                        <tr><td>{{ $b->component }}</td><td class="num">{{ $b->amount }}</td><td class="num">{{ rupiah($b->cost) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty">Resep belum diisi</td></tr>
                    @endforelse
                </tbody></table>
                <div style="padding:14px 20px 18px">
                    <div class="kv"><span class="muted">HPP per porsi</span><b>{{ rupiah($menu->hpp) }}</b></div>
                    <div class="kv"><span class="muted">Harga jual</span><b>{{ rupiah($menu->selling_price) }}</b></div>
                    <div class="kv total"><span>Margin</span><span>{{ $menu->margin_percent !== null ? str_replace('.', ',', $menu->margin_percent).'%' : '—' }}</span></div>
                </div>
            </div>
        @endif
        <div class="card"><h2>Varian diet otomatis</h2>
            @foreach ($variants as $v)
                <div class="list-item">
                    <div class="col grow"><b>{{ $v->name }}</b><span class="sub">{{ $v->rule }}</span></div>
                    <form method="POST" action="{{ route('menus.variant', $v) }}">@csrf @method('PATCH')<button class="switch-btn" aria-pressed="{{ $v->active ? 'true' : 'false' }}" aria-label="{{ $v->name }}"></button></form>
                </div>
            @endforeach
            <div class="note">Label varian ikut tercetak di kemasan, sehingga dapur dan kurir tidak tertukar.</div>
        </div>
    </div>
@endsection

@push('templates')
    <template id="tpl-menu">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Menu baru</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="mnForm" method="POST" action="{{ route('menus.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field full"><label class="l" for="mn-n">Nama menu</label><input id="mn-n" name="name" type="text" required></div>
                <div class="field"><label class="l" for="mn-p">Harga jual per porsi</label><input id="mn-p" name="selling_price" type="number" min="0" value="28000" required></div>
                <div class="field"><label class="l" for="mn-w">Masuk rotasi (opsional)</label>
                    <div class="row">
                        <select id="mn-w" name="week" aria-label="Minggu"><option value="">Minggu</option>@foreach (range(1, 4) as $w)<option value="{{ $w }}">Minggu {{ $w }}</option>@endforeach</select>
                        <select name="weekday" aria-label="Hari"><option value="">Hari</option>@foreach (\App\Models\MenuRotation::WEEKDAYS as $k => $d)<option value="{{ $k }}">{{ $d }}</option>@endforeach</select>
                    </div>
                </div>
                @foreach ([['Protein utama', '120 g'], ['Bumbu & rempah', '25 g'], ['Nasi', '180 g'], ['Sayur pendamping', '80 g'], ['Sambal, kerupuk, buah', '—'], ['Kemasan', '1 box']] as $i => [$c, $a])
                    <div class="field full"><div class="row">
                        <input name="items[{{ $i }}][component]" type="text" value="{{ $c }}" aria-label="Bahan {{ $i + 1 }}">
                        <input name="items[{{ $i }}][amount]" type="text" value="{{ $a }}" aria-label="Takaran {{ $i + 1 }}" style="width:110px">
                        <input name="items[{{ $i }}][cost]" type="number" min="0" placeholder="Biaya" aria-label="Biaya {{ $i + 1 }}" style="width:130px">
                    </div></div>
                @endforeach
            </div>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="mnForm" type="submit">Simpan menu</button></div>
    </template>
@endpush
