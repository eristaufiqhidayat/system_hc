@extends('layouts.mobile')
@section('title', 'Alamat & preferensi · '.config('catering.name'))

@section('content')
    @include('shop._top')
    <div class="p-body">
        @include('shop._steps', ['step' => 2])
        <a class="btn ghost sm" href="{{ route('shop.start') }}" style="align-self:flex-start">← Kembali</a>
        <h2>Preferensi & alamat</h2>
        @if ($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('shop.details.save') }}">
            @csrf
            <div class="field"><label class="l" for="w-n">Nama</label><input id="w-n" name="name" type="text" value="{{ old('name', $cart['name'] ?? '') }}" required></div>
            <div class="field"><label class="l" for="w-w">No. WhatsApp</label><input id="w-w" name="whatsapp" type="tel" value="{{ old('whatsapp', $cart['whatsapp'] ?? '') }}" placeholder="0812-xxxx-xxxx" required></div>
            <div class="field"><label class="l" for="w-a">Alamat antar</label><input id="w-a" name="address" type="text" value="{{ old('address', $cart['address'] ?? '') }}" placeholder="Jl. Mawar 12, Bintaro Sektor 3" required></div>
            <div class="form-grid">
                <div class="field"><label class="l" for="w-s">Mulai</label><input id="w-s" name="start_date" type="date" min="{{ today()->addDay()->toDateString() }}" value="{{ old('start_date', $cart['start_date']) }}" required></div>
                <div class="field"><label class="l" for="w-j">Jam antar</label><select id="w-j" name="window">@foreach (config('catering.delivery_windows') as $w)<option @selected(old('window', $cart['window']) === $w)>{{ $w }}</option>@endforeach</select></div>
            </div>
            <div>
                @foreach (config('catering.preferences') as $p)
                    <label class="check"><input type="checkbox" name="preferences[]" value="{{ $p }}" @checked(in_array($p, old('preferences', $cart['preferences'] ?? []), true))>{{ $p }}</label>
                @endforeach
            </div>
            <div class="field"><label class="l" for="w-c">Alergi / catatan</label><input id="w-c" name="notes" type="text" value="{{ old('notes', $cart['notes'] ?? '') }}" placeholder="Contoh: alergi kacang"></div>
            <div class="card" style="padding:14px;gap:2px">
                <div class="kv"><span>{{ $pricing['label'] }}</span><b>{{ rupiah($pricing['subtotal']) }}</b></div>
                <div class="kv"><span>Ongkir</span><b>Gratis</b></div>
                <div class="kv total"><span>Total</span><span>{{ rupiah($pricing['total']) }}</span></div>
            </div>
            <button class="btn pri big" type="submit">Bayar dengan QRIS</button>
        </form>
    </div>
@endsection
