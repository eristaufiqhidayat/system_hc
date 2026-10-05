@extends('layouts.mobile')
@section('title', 'Pesan · '.config('catering.name'))

@section('content')
    @include('shop._top')
    <div class="p-hero"><h2>Masakan rumahan, diantar setiap hari</h2><small>Tangerang & Bintaro · Chef bersertifikasi BNSP · anggota ICA</small></div>
    <div class="p-body">
        @include('shop._steps', ['step' => 1])
        <form method="POST" action="{{ route('shop.start.save') }}">
            @csrf
            <input type="hidden" name="service" value="{{ $cart['service'] }}">
            <input type="hidden" name="package" value="{{ $cart['package'] }}">
            <input type="hidden" name="portions" value="{{ $cart['portions'] }}">
            <div class="pills" role="tablist">
                @foreach (array_keys($services) as $s)
                    @php $firstPkg = array_key_first($services[$s]['packages']); @endphp
                    <button class="pill" role="tab" aria-selected="{{ $s === $cart['service'] ? 'true' : 'false' }}" name="action" value="svc"
                            formaction="{{ route('shop.start.save') }}" onclick="this.form.service.value='{{ $s }}';this.form.package.value='{{ $s === $cart['service'] ? $cart['package'] : $firstPkg }}'">{{ $s }}</button>
                @endforeach
            </div>
            <h2>Pilih paket</h2>
            @foreach ($packages as $key => $p)
                <button class="opt" type="submit" name="action" value="pkg" aria-pressed="{{ $key === $cart['package'] ? 'true' : 'false' }}" onclick="this.form.package.value='{{ $key }}'">
                    <span class="col grow"><b>{{ $p['label'] }}</b><span class="sub">{{ $p['hint'] }}</span></span>
                    <b style="color:var(--green)">{{ rupiah($p['price'] * $p['days']) }}</b>
                </button>
            @endforeach
            <div class="between"><b>Jumlah porsi</b>
                <div class="row">
                    <button class="btn sm" type="submit" name="action" value="por" aria-label="Kurangi" onclick="this.form.portions.value=Math.max(1,{{ $cart['portions'] }}-1)">−</button>
                    <b style="width:24px;text-align:center">{{ $cart['portions'] }}</b>
                    <button class="btn sm" type="submit" name="action" value="por" aria-label="Tambah" onclick="this.form.portions.value={{ $cart['portions'] }}+1">+</button>
                </div>
            </div>
            <div class="card" style="padding:14px;gap:8px"><h3>Menu minggu ini</h3>
                @foreach ($weekMenus as $i => $m)
                    <div class="row" style="font-size:13px"><b style="width:32px;color:var(--green)">{{ ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'][$i] ?? '' }}</b>{{ $m->name }}</div>
                @endforeach
            </div>
            <button class="btn pri big" type="submit" name="action" value="next">Lanjut</button>
        </form>
    </div>
@endsection
