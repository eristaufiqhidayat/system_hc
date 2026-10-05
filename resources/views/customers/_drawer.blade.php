<div class="dh">
    <div class="row">
        <div class="av" style="width:48px;height:48px;font-size:16px">{{ $customer->initials }}</div>
        <div class="col"><h2 style="font-size:20px">{{ $customer->name }}</h2><span class="sub">{{ $customer->segment }} · {{ $customer->area }}</span></div>
    </div>
    <button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button>
</div>
<div class="db">
    <div class="g2">
        <x-kpi label="Pesanan" :value="$customer->order_summary">Sejak {{ $customer->customer_since ? month_id($customer->customer_since->month).' '.$customer->customer_since->year : '—' }}</x-kpi>
        <x-kpi label="Terakhir" :value="$lastOrder ? date_id($lastOrder->delivery_date) : '—'">{{ $lastOrder ? 'Via '.$lastOrder->channel : 'Belum ada pesanan' }}</x-kpi>
    </div>
    <div class="card" style="padding:16px;gap:4px">
        <div class="kv"><span class="muted">WhatsApp</span><b>{{ $customer->whatsapp ?: '—' }}</b></div>
        <div class="kv"><span class="muted">Alamat</span><b style="text-align:right">{{ $customer->address ?: $customer->area }}</b></div>
        <div class="kv"><span class="muted">Preferensi</span><b>{{ $customer->preference ?: '—' }}</b></div>
        <div class="kv"><span class="muted">Ulang tahun</span><b>{{ $customer->birthday ? date_id($customer->birthday) : '—' }}</b></div>
    </div>
    <div>
        <h3 style="margin-bottom:10px">Riwayat</h3>
        @forelse ($orders as $o)
            <div class="list-item"><div class="col grow"><b>{{ $o->item }}</b><span class="sub">#{{ $o->code }} · {{ $o->schedule_label }}</span></div><x-badge :tone="$o->status_tone">{{ $o->status }}</x-badge></div>
        @empty
            <span class="muted">Belum ada pesanan.</span>
        @endforelse
    </div>
    <div class="actions">
        <button class="btn pri" type="button" data-modal="tpl-new-order">Buat pesanan</button>
        @if ($customer->whatsapp)
            <a class="btn" href="https://wa.me/62{{ ltrim(preg_replace('/\D/', '', $customer->whatsapp), '0') }}" target="_blank" rel="noopener"><x-icon name="chat"/>Chat</a>
        @endif
        @if ($customer->subscriptions()->exists())
            <a class="btn ghost" href="{{ route('portal.show', $customer->portal_token) }}" target="_blank">Akun pelanggan</a>
        @endif
    </div>
</div>
