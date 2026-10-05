<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('catering.name'))</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/extra.css') }}">
</head>
<body class="mobile" data-toast-flash="{{ session('toast') }}">
<div class="mobile-shell">
    @yield('content')
</div>
<div class="toast" id="toast" role="status"><x-icon name="check" stroke-width="2.5"/><span id="toastTxt"></span></div>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
