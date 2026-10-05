<table>
    <thead>
        <tr><th>No.</th><th>Pelanggan</th><th>Lini</th><th class="num">Porsi</th><th>Jadwal</th><th>Masuk via</th><th>Pembayaran</th><th>Status</th></tr>
    </thead>
    <tbody>
        @forelse ($orders as $o)
            <tr class="click" tabindex="0" data-drawer-url="{{ route('orders.show', $o) }}">
                <td class="muted" style="white-space:nowrap">#{{ $o->code }}</td>
                <td><div class="col"><b>{{ $o->customer->name }}</b><span class="sub">{{ $o->area }}</span></div></td>
                <td>{{ $o->business_line }}</td>
                <td class="num">{{ qty($o->portions) }}</td>
                <td>{{ $o->schedule_label }}</td>
                <td>{{ $o->channel }}</td>
                <td><x-badge :tone="$o->payment_tone">{{ $o->payment_label }}</x-badge></td>
                <td><x-badge :tone="$o->status_tone">{{ $o->status }}</x-badge></td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted" style="text-align:center;padding:30px">Tidak ada pesanan</td></tr>
        @endforelse
    </tbody>
</table>
