@extends('layouts.app')
@section('title', 'Pesanan')

@section('content')
    <x-page-head eyebrow="Semua pesanan dari web, WhatsApp bot, admin & kontrak ·" title="Pesanan">
        <x-slot:actions>
            <a class="btn" href="{{ route('orders.export') }}"><x-icon name="download"/>Ekspor</a>
            <button class="btn pri" type="button" data-modal="tpl-new-order"><x-icon name="plus"/>Pesanan baru</button>
        </x-slot:actions>
    </x-page-head>

    <div class="card pad0">
        <div class="tabs" role="tablist" style="padding:0 12px">
            @foreach (['Semua', ...\App\Models\Order::STATUSES] as $tab)
                <a class="tab" role="tab" aria-selected="{{ $tab === $status ? 'true' : 'false' }}"
                   href="{{ route('orders.index', array_filter(['status' => $tab === 'Semua' ? null : $tab, 'lini' => $line === 'Semua' ? null : $line, 'q' => $q])) }}">
                    {{ $tab }}<span class="n">{{ $tab === 'Semua' ? $counts->sum() : ($counts[$tab] ?? 0) }}</span>
                </a>
            @endforeach
        </div>
        <div class="between" style="padding:14px 16px;flex-wrap:wrap">
            <div class="chips">
                @foreach (['Semua', ...\App\Models\Order::LINES] as $l)
                    <a class="chip" aria-pressed="{{ $l === $line ? 'true' : 'false' }}"
                       href="{{ route('orders.index', array_filter(['status' => $status === 'Semua' ? null : $status, 'lini' => $l === 'Semua' ? null : $l, 'q' => $q])) }}">{{ $l }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('orders.index') }}" style="width:260px">
                @if ($status !== 'Semua')<input type="hidden" name="status" value="{{ $status }}">@endif
                @if ($line !== 'Semua')<input type="hidden" name="lini" value="{{ $line }}">@endif
                <label class="sr-only" for="oq">Cari pesanan</label>
                <input id="oq" name="q" type="search" placeholder="Cari nama / no. pesanan" value="{{ $q }}">
            </form>
        </div>
        <div class="tablewrap">@include('partials.order-table', ['orders' => $orders])</div>
        {{ $orders->links() }}
    </div>
@endsection
