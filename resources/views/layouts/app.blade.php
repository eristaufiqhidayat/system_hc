<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Sistem HC Catering</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/extra.css') }}">
</head>
<body data-toast-flash="{{ session('toast') }}" data-open-drawer="{{ session('open_drawer') ?? (request('buka') ? route('orders.show', request('buka')) : '') }}">
<div class="app">
    <aside class="side" id="side">
        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="Logo HC Catering">
            <div><b>Sistem HC</b><small>{{ config('catering.area') }}</small></div>
        </div>
        <nav class="nav" aria-label="Menu utama">
            @foreach ($nav as $group => $items)
                <div class="grp">{{ $group }}</div>
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}" @if ($item['active']) aria-current="page" @endif>
                        <x-icon :name="$item['icon']"/><span>{{ $item['label'] }}</span>
                        @if ($item['count'])<span class="cnt">{{ $item['count'] }}</span>@endif
                    </a>
                @endforeach
            @endforeach
        </nav>
        <div class="me">
            <div class="av">{{ auth()->user()->initials }}</div>
            <div class="col grow"><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role_label }}</small></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="x" aria-label="Keluar" title="Keluar"><x-icon name="logout"/></button>
            </form>
        </div>
    </aside>

    <div class="content">
        <header class="top">
            <button class="iconbtn menu-btn" id="menuBtn" type="button" aria-label="Buka menu"><x-icon name="menu"/></button>
            <form class="search" method="GET" action="{{ route('search') }}" role="search">
                <x-icon name="search"/>
                <label for="gsearch" class="sr-only">Cari</label>
                <input id="gsearch" name="q" type="search" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Cari pesanan, pelanggan, invoice…">
            </form>
            <div class="spacer"></div>
            <span class="today">{{ date_long_id(today()) }}</span>
            <a class="iconbtn" href="{{ route('dashboard') }}" aria-label="Notifikasi, {{ $notifications }} baru">
                <x-icon name="bell"/>@if ($notifications)<span class="pip"></span>@endif
            </a>
        </header>
        <main id="main">
            @if ($errors->any())
                <div class="alert" role="alert">
                    <b>Periksa kembali isian:</b>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<div class="scrim" id="scrim"></div>
<aside class="drawer" id="drawer" aria-hidden="true"></aside>
<div class="modal" id="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle"></div>
<div class="toast" id="toast" role="status"><x-icon name="check" stroke-width="2.5"/><span id="toastTxt"></span></div>

@if (auth()->user()->canAccess('orders'))
    @include('orders._new-modal')
@endif
@stack('templates')

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
