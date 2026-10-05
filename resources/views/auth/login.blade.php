<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Sistem HC Catering</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/extra.css') }}">
</head>
<body>
<div class="auth">
    <form class="card" method="POST" action="{{ url('/login') }}">
        @csrf
        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="Logo HC Catering">
            <div><b>Sistem HC</b><small>{{ config('catering.area') }}</small></div>
        </div>
        <h2 style="text-align:center">Masuk ke dashboard</h2>
        @if ($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif
        <div class="field">
            <label class="l" for="email">Email</label>
            <input id="email" name="email" type="text" inputmode="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="field">
            <label class="l" for="password">Kata sandi</label>
            <input id="password" name="password" type="password" required style="height:42px;border-radius:10px;border:1px solid #CBD2CB;padding:0 12px">
        </div>
        <label class="check"><input type="checkbox" name="remember" value="1">Ingat saya</label>
        <button class="btn pri big" type="submit">Masuk</button>
        @if (app()->environment('local'))
            <div class="hint">Akun contoh: <b>owner@hccatering.test</b> / <b>password</b> (juga admin@, dapur@, keuangan@, rudi@ untuk kurir)</div>
        @endif
    </form>
</div>
</body>
</html>
