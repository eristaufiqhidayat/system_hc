@php
    $steps = \App\Models\Order::STATUSES;
    $at = array_search($order->status, $steps, true);
    $times = $order->histories->keyBy('status');
@endphp
<div class="dh">
    <div class="col">
        <span class="sub">Pesanan #{{ $order->code }} · masuk via {{ $order->channel }}</span>
        <h2 style="font-size:20px">{{ $order->customer->name }}</h2>
        <span class="sub">{{ $order->area }}</span>
    </div>
    <button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button>
</div>
<div class="db">
    <div class="row"><x-badge :tone="$order->status_tone">{{ $order->status }}</x-badge> <x-badge :tone="$order->payment_tone">{{ $order->payment_label }}</x-badge></div>
    <div class="card" style="padding:16px;gap:4px">
        <div class="kv"><span class="muted">Lini bisnis</span><b>{{ $order->business_line }}</b></div>
        <div class="kv"><span class="muted">Item</span><b>{{ $order->item }}</b></div>
        <div class="kv"><span class="muted">Porsi</span><b>{{ qty($order->portions) }}</b></div>
        <div class="kv"><span class="muted">Jadwal antar</span><b>{{ $order->schedule_label }}</b></div>
        <div class="kv"><span class="muted">Alamat</span><b style="text-align:right">{{ $order->address ?: '—' }}</b></div>
        <div class="kv"><span class="muted">Preferensi</span><b>{{ $order->notes ?: ($order->customer->preference ?: '—') }}</b></div>
        <div class="kv total"><span>Total</span><span>{{ $order->total ? rupiah($order->total) : 'Masuk invoice kontrak' }}</span></div>
    </div>
    <div>
        <h3 style="margin-bottom:12px">Riwayat status</h3>
        <div class="timeline">
            @foreach ($steps as $i => $s)
                <div class="tl {{ $i <= $at ? 'done' : '' }}"><i></i>
                    <div class="col"><b>{{ $s }}</b><span class="sub">{{ isset($times[$s]) ? date_id($times[$s]->changed_at).' · '.$times[$s]->changed_at->format('H.i') : 'Belum' }}</span></div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="actions">
        @if ($order->next_status)
            <form method="POST" action="{{ route('orders.advance', $order) }}">@csrf<button class="btn pri">Tandai {{ mb_strtolower($order->next_status) }}</button></form>
        @endif
        <form method="POST" action="{{ route('orders.whatsapp', $order) }}">@csrf<button class="btn"><x-icon name="send"/>Kirim ke WhatsApp</button></form>
        <a class="btn" href="{{ route('orders.receipt', $order) }}" target="_blank"><x-icon name="print"/>Cetak nota</a>
    </div>
</div>
