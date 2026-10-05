@extends('layouts.mobile')
@section('title', 'Pembayaran · '.config('catering.name'))

@section('content')
    @include('shop._top')
    <div class="p-body" style="text-align:center">
        @include('shop._steps', ['step' => 3])
        <h2>Scan untuk bayar</h2><span class="sub">Bisa pakai semua e-wallet & m-banking</span>
        <div class="qr"><x-qr :seed="crc32($cart['whatsapp'] ?? 'hc') % 1000"/></div>
        <b style="font-size:22px">{{ rupiah($pricing['total']) }}</b>
        <span class="sub">Berlaku 15 menit · Hubungkan payment gateway untuk QRIS dinamis</span>
        <form method="POST" action="{{ route('shop.confirm') }}">@csrf<button class="btn pri big" type="submit">Saya sudah bayar</button></form>
        <a class="btn ghost" href="{{ route('shop.details') }}">Ganti data pesanan</a>
    </div>
@endsection
