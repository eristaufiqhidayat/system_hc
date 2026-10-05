@extends('layouts.app')
@section('title', 'Akun Pelanggan')

@section('content')
    <x-page-head eyebrow="Pelanggan mengatur langganannya sendiri ·" title="Akun Pelanggan (HP)">
        <x-slot:actions><a class="btn" href="{{ route('portal.show', $customer->portal_token) }}" target="_blank">Buka di tab baru</a></x-slot:actions>
    </x-page-head>
    <div class="phones">
        <div class="phone"><iframe src="{{ route('portal.show', $customer->portal_token) }}" title="Akun pelanggan {{ $customer->name }}"></iframe></div>
        <div class="side-note"><div class="note">Pelanggan bisa <b>melewati hari</b>, <b>menjeda</b>, <b>mengubah preferensi</b>, dan <b>memperpanjang</b> sendiri lewat tautan unik yang dikirim via WhatsApp. Admin HC tidak perlu lagi mencatat perubahan dari chat satu per satu. Contoh ini memakai akun {{ $customer->name }}.</div></div>
    </div>
@endsection
