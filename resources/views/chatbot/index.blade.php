@extends('layouts.app')
@section('title', 'Chatbot WhatsApp')

@section('content')
    <x-page-head eyebrow="Membalas otomatis 24 jam di nomor WhatsApp Business HC ·" title="Chatbot WhatsApp">
        <x-slot:actions>
            <form class="row strong" method="POST" action="{{ route('chatbot.toggle') }}" style="gap:10px">
                @csrf<span>Bot {{ $active ? 'aktif' : 'mati' }}</span><button class="switch-btn" aria-pressed="{{ $active ? 'true' : 'false' }}" aria-label="Bot aktif"></button>
            </form>
        </x-slot:actions>
    </x-page-head>

    <div class="g4">
        <x-kpi label="Chat minggu ini" :value="$stats['chats']">Pesan masuk 7 hari terakhir</x-kpi>
        <x-kpi label="Dijawab otomatis" :value="$stats['auto_percent'].'%'">Sisanya diteruskan ke admin</x-kpi>
        <x-kpi label="Pesanan via bot" :value="$botOrders">{{ rupiah_short($botRevenue) }} omzet</x-kpi>
        <x-kpi label="Waktu balas" value="< 5 detik">Balasan bot otomatis</x-kpi>
    </div>

    <div class="phones">
        <div class="phone">
            <div class="wa-head"><div class="av" style="width:36px;height:36px;overflow:hidden;background:#fff"><img src="{{ asset('images/logo.png') }}" alt="" style="height:26px"></div><div class="col"><b>{{ config('catering.name') }}</b><small style="color:#CFE7D6">Akun bisnis · {{ $active ? 'online' : 'bot mati' }}</small></div></div>
            <div class="scr"><div class="chat" id="chat">
                <div class="bub out">Halo, mau tanya catering harian untuk kantor ada?<span class="t">09.14 ✓✓</span></div>
                <div class="bub in">Halo, selamat pagi! 👋 Terima kasih sudah menghubungi {{ config('catering.name') }}.
Silakan pilih:
1️⃣ Rantangan harian (rumah/kos)
2️⃣ Makan karyawan kantor
3️⃣ Prasmanan & acara
4️⃣ Lihat menu minggu ini<span class="t">09.14</span></div>
            </div></div>
            <form class="wa-in" id="waForm" action="{{ route('chatbot.simulate') }}">
                <label for="waMsg" class="sr-only">Ketik pesan</label>
                <input id="waMsg" type="text" placeholder="Coba ketik: menu / harga / pesan" autocomplete="off">
                <button class="btn pri" style="border-radius:999px;width:44px;padding:0" aria-label="Kirim"><x-icon name="send"/></button>
            </form>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px;flex:1;min-width:280px;max-width:560px">
            <div class="card"><h2>Balasan otomatis</h2>
                @foreach ($rules as $r)
                    <details class="edit list-item" style="display:block">
                        <summary class="row"><span class="col grow"><b>{{ $r->keywords }}</b><span class="sub">{{ $r->description }}</span></span><span class="btn sm ghost">Ubah</span></summary>
                        <form method="POST" action="{{ route('chatbot.rules.update', $r) }}" class="col" style="gap:10px;margin-top:10px">
                            @csrf @method('PUT')
                            <div class="field"><label class="l">Kata kunci (pisahkan dengan koma)</label><input name="keywords" type="text" value="{{ $r->keywords }}" required></div>
                            <div class="field"><label class="l">Keterangan</label><input name="description" type="text" value="{{ $r->description }}" required></div>
                            <div class="field"><label class="l">Balasan</label><textarea name="response" rows="4" required>{{ $r->response }}</textarea><span class="sub">Gunakan {menu_minggu_ini} untuk menyisipkan menu minggu ini.</span></div>
                            <div class="field"><label class="l">Tombol cepat</label><input name="quick_button" type="text" value="{{ $r->quick_button }}"></div>
                            <label class="check"><input type="checkbox" name="forward_to_admin" value="1" @checked($r->forward_to_admin)>Teruskan juga ke admin</label>
                            <button class="btn pri sm" style="align-self:flex-start">Simpan</button>
                        </form>
                    </details>
                @endforeach
            </div>
            <div class="card"><h2>Pesan terjadwal</h2>
                @foreach ($scheduled as $s)
                    <div class="list-item">
                        <div class="col grow"><b>{{ $s->name }}</b><span class="sub">{{ str_replace('ke pelanggan langganan', 'ke '.$subscribers.' pelanggan langganan', $s->schedule) }}</span></div>
                        <form method="POST" action="{{ route('chatbot.scheduled', $s) }}">@csrf @method('PATCH')<button class="switch-btn" aria-pressed="{{ $s->active ? 'true' : 'false' }}" aria-label="{{ $s->name }}"></button></form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
