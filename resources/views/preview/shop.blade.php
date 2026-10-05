@extends('layouts.app')
@section('title', 'Web Pemesanan')

@section('content')
    <x-page-head eyebrow="Link dari Instagram, Google & chatbot WhatsApp ·" title="Web Pemesanan (HP)">
        <x-slot:actions><a class="btn" href="{{ route('shop.start') }}" target="_blank">Buka di tab baru</a></x-slot:actions>
    </x-page-head>
    <div class="phones">
        <div class="phone"><iframe src="{{ route('shop.start') }}" title="Web pemesanan"></iframe></div>
        <div class="side-note">
            <div class="note"><b>Coba alurnya:</b> pilih layanan & paket → isi alamat & preferensi → bayar QRIS → konfirmasi. Tiga langkah, tanpa chat bolak-balik. Tautan publik: <b>{{ route('shop.start') }}</b></div>
            <div class="card"><h2>Yang terjadi otomatis setelah bayar</h2>
                @foreach (['Pesanan masuk dashboard dengan status Lunas', 'Porsi & preferensi masuk rekap dapur', 'Alamat masuk rute kurir terdekat', 'Konfirmasi & jadwal dikirim ke WhatsApp pelanggan', 'Pengingat terkirim H-2 sebelum paket habis'] as $t)
                    <div class="row" style="align-items:flex-start"><span style="color:var(--green);margin-top:2px"><x-icon name="check" size="16"/></span><span>{{ $t }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
