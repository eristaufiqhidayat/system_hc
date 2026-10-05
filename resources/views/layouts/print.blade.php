<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('catering.name') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/extra.css') }}">
</head>
<body class="print">
    <div class="print-bar no-print"><button class="btn pri" onclick="window.print()"><x-icon name="print"/>Cetak</button><a class="btn" href="{{ url()->previous() }}">Kembali</a></div>
    @yield('content')
</body>
</html>
