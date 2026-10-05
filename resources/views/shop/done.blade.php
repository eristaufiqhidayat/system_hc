@extends('layouts.mobile')
@section('title', 'Pembayaran berhasil · '.config('catering.name'))

@section('content')
    @include('shop._top')
    <div class="p-body" style="text-align:center;padding-top:40px;gap:14px">
        <div style="width:84px;height:84px;border-radius:50%;background:var(--green-soft);margin:0 auto;display:flex;align-items:center;justify-content:center;color:var(--green)"><x-icon name="check" size="44"/></div>
        <h2 style="font-size:22px">Pembayaran berhasil!</h2>
        <span class="muted">Pesanan #{{ $order->code }} terkonfirmasi. Detail & jadwal antar sudah dikirim ke WhatsApp Anda.</span>
        <div class="card" style="padding:14px;text-align:left;gap:2px">
            <div class="kv"><span class="muted">Paket</span><b>{{ $order->item }}</b></div>
            <div class="kv"><span class="muted">Mulai</span><b>{{ day_id($order->delivery_date) }}, {{ date_id($order->delivery_date) }}</b></div>
            <div class="kv"><span class="muted">Antar</span><b>{{ $order->delivery_time }}</b></div>
            <div class="kv total"><span>Total</span><span>{{ rupiah($order->total) }}</span></div>
        </div>
        @if ($order->business_line === 'Rantangan')
            <a class="btn pri big" href="{{ route('portal.show', $order->customer->portal_token) }}">Lihat akun saya</a>
        @endif
        <a class="btn ghost" href="{{ route('shop.start') }}">Pesan lagi</a>
    </div>
@endsection
