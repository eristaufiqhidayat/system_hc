@extends('layouts.app')
@section('title', 'Aplikasi Kurir')

@section('content')
    <x-page-head eyebrow="Urutan titik dari sistem, bukti antar dengan foto ·" title="Aplikasi Kurir (HP)">
        <x-slot:actions><a class="btn" href="{{ route('courier.app') }}" target="_blank">Buka di tab baru</a></x-slot:actions>
    </x-page-head>
    <div class="phones">
        <div class="phone"><iframe src="{{ route('courier.app') }}" title="Aplikasi kurir"></iframe></div>
        <div class="side-note"><div class="note">Klik <b>“Tandai terkirim”</b>. Progres rute di halaman Dashboard & Pengiriman ikut bertambah, dan pelanggan menerima notifikasi WhatsApp bahwa makanan sudah sampai. Kurir yang login hanya melihat rutenya sendiri.</div></div>
    </div>
@endsection
