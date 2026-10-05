@extends('layouts.app')
@section('title', 'Pencarian')

@section('content')
    <x-page-head eyebrow="Hasil pencarian ·" :title="$q ? '“'.$q.'”' : 'Cari'"/>

    @if (! $q)
        <div class="card"><span class="muted">Ketik nama pelanggan, nomor pesanan, atau nomor invoice di kolom pencarian.</span></div>
    @else
        <div class="card pad0 search-group">
            <div style="padding:18px 20px 0"><h2>Pesanan · {{ $orders->count() }}</h2></div>
            <div class="tablewrap">@include('partials.order-table', ['orders' => $orders])</div>
        </div>
        <div class="g2">
            <div class="card">
                <h2>Pelanggan · {{ $customers->count() }}</h2>
                @forelse ($customers as $c)
                    <div class="list-item" style="cursor:pointer" tabindex="0" data-drawer-url="{{ route('customers.show', $c) }}">
                        <div class="av">{{ $c->initials }}</div>
                        <div class="col grow"><b>{{ $c->name }}</b><span class="sub">{{ $c->segment }} · {{ $c->area }}</span></div>
                    </div>
                @empty
                    <span class="muted">Tidak ada pelanggan cocok.</span>
                @endforelse
            </div>
            @if (auth()->user()->canAccess('billing'))
                <div class="card">
                    <h2>Invoice · {{ $invoices->count() }}</h2>
                    @forelse ($invoices as $i)
                        <a class="list-item" href="{{ route('invoices.index') }}" style="color:inherit;text-decoration:none">
                            <div class="col grow"><b>{{ $i->number }}</b><span class="sub">{{ $i->client_label }} · {{ $i->period }}</span></div>
                            <x-badge :tone="$i->state[1]">{{ $i->state[0] }}</x-badge>
                        </a>
                    @empty
                        <span class="muted">Tidak ada invoice cocok.</span>
                    @endforelse
                </div>
            @endif
        </div>
    @endif
@endsection
