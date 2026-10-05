@extends('layouts.print')
@section('title', 'Nota #'.$order->code)

@section('content')
    <div class="card" style="max-width:520px">
        <div class="between">
            <div class="row"><img src="{{ asset('images/logo.png') }}" alt="" style="height:44px"><div class="col"><b>{{ config('catering.name') }}</b><span class="sub">{{ config('catering.area') }}</span></div></div>
            <div class="col" style="text-align:right"><b>Nota #{{ $order->code }}</b><span class="sub">{{ date_id($order->created_at, true) }}</span></div>
        </div>
        <div class="kv"><span class="muted">Pelanggan</span><b>{{ $order->customer->name }}</b></div>
        <div class="kv"><span class="muted">Alamat</span><b style="text-align:right">{{ $order->address ?: $order->area }}</b></div>
        <div class="kv"><span class="muted">Item</span><b>{{ $order->item }}</b></div>
        <div class="kv"><span class="muted">Porsi</span><b>{{ qty($order->portions) }}</b></div>
        <div class="kv"><span class="muted">Jadwal antar</span><b>{{ $order->schedule_label }}</b></div>
        <div class="kv"><span class="muted">Pembayaran</span><b>{{ $order->payment_label }}</b></div>
        <div class="kv total"><span>Total</span><span>{{ $order->total ? rupiah($order->total) : 'Invoice kontrak' }}</span></div>
        <span class="sub" style="text-align:center">Terima kasih telah memesan di {{ config('catering.name') }}.</span>
    </div>
@endsection
