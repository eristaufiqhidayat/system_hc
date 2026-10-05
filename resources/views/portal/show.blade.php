@extends('layouts.mobile')
@section('title', 'Akun saya · '.config('catering.name'))

@section('content')
    <div class="p-top"><img src="{{ asset('images/logo.png') }}" alt="{{ config('catering.name') }}"><div class="col grow"><b>Halo, {{ $customer->name }}</b><span class="sub">{{ $customer->area }}</span></div></div>
    <div class="p-body">
        @if ($subscription)
            @php $pct = $subscription->total_days ? round($subscription->remaining_days / $subscription->total_days * 100) : 0; @endphp
            <div class="card" style="background:var(--green);border-color:var(--green);color:#fff;gap:8px">
                <span style="font-size:13px;color:#D5EEDC">{{ $subscription->isPaused() ? 'Langganan dijeda s/d '.date_id($subscription->paused_until) : 'Langganan aktif' }}</span>
                <b style="font-size:20px">Rantangan {{ mb_strtolower($subscription->package_label) }} · {{ $subscription->portions }} porsi</b>
                <div class="between" style="font-size:13px"><span>Sisa {{ $subscription->remaining_days }} dari {{ $subscription->total_days }} hari</span><span>s/d {{ date_id($subscription->ends_at) }}</span></div>
                <div class="prog"><i style="width:{{ $pct }}%"></i></div>
                <form method="POST" action="{{ route('portal.renew', $customer->portal_token) }}">@csrf
                    <button class="btn" style="background:#fff;border-color:#fff;color:var(--green-ink);margin-top:4px;width:100%">Perpanjang sekarang</button>
                </form>
            </div>

            <h2>Jadwal minggu depan</h2>
            <div>
                @foreach ($schedule as $d)
                    <div class="list-item" style="{{ $d['skipped'] ? 'opacity:.5' : '' }}">
                        <span class="grow" style="font-size:13px"><b>{{ day_id($d['date'], true) }} {{ date_id($d['date']) }}</b><br><span class="sub">{{ $d['menu'] }}</span></span>
                        <form method="POST" action="{{ route('portal.skip', $customer->portal_token) }}">@csrf
                            <input type="hidden" name="date" value="{{ $d['date']->toDateString() }}">
                            <button class="btn sm">{{ $d['skipped'] ? 'Batal lewati' : 'Lewati' }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
            <div class="note" style="font-size:12px">Hari yang dilewati otomatis menambah masa langganan.</div>

            <h2>Preferensi</h2>
            <form method="POST" action="{{ route('portal.preferences', $customer->portal_token) }}" class="card" style="padding:12px 14px;gap:0">
                @csrf
                @foreach ($preferences as $p)
                    <div class="between" style="min-height:44px"><span>{{ $p }}</span>
                        <label class="switch"><input type="checkbox" name="preferences[]" value="{{ $p }}" @checked(in_array($p, $activePrefs, true)) aria-label="{{ $p }}" onchange="this.form.submit()"><span></span></label>
                    </div>
                @endforeach
            </form>
        @else
            <div class="card">Belum ada langganan aktif. <a href="{{ route('shop.start') }}">Pesan sekarang</a></div>
        @endif

        <h2>Riwayat pembayaran</h2>
        @forelse ($payments as $p)
            <div class="list-item"><div class="col grow"><b style="font-size:13px">{{ $p->description }}</b><span class="sub">{{ date_id($p->paid_at) }} · {{ $p->method }}</span></div><x-badge tone="ok">Lunas</x-badge></div>
        @empty
            <span class="muted">Belum ada pembayaran.</span>
        @endforelse

        @if ($subscription)
            <form method="POST" action="{{ route('portal.pause', $customer->portal_token) }}">@csrf
                <button class="btn" style="width:100%"><x-icon name="pause"/>{{ $subscription->isPaused() ? 'Lanjutkan langganan' : 'Jeda langganan 1 minggu' }}</button>
            </form>
        @endif
        <a class="btn ghost" href="https://wa.me/{{ preg_replace('/\D/', '', config('services.whatsapp.business_number', '')) }}" target="_blank" rel="noopener"><x-icon name="chat"/>Hubungi {{ config('catering.name') }}</a>
    </div>
@endsection
