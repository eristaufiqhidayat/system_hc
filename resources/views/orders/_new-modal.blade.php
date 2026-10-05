<template id="tpl-new-order">
    <div class="mh"><h2 id="modalTitle" style="font-size:18px">Pesanan baru</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
    <form class="mb" id="noForm" method="POST" action="{{ route('orders.store') }}">
        @csrf
        <div class="form-grid">
            <div class="field full"><label class="l" for="no-c">Pelanggan</label>
                <input id="no-c" name="customer" type="text" list="custs" placeholder="Ketik nama atau pilih pelanggan lama" value="{{ old('customer') }}" required>
                <datalist id="custs">@foreach ($customerNames as $name)<option value="{{ $name }}">@endforeach</datalist>
            </div>
            <div class="field"><label class="l" for="no-l">Lini bisnis</label>
                <select id="no-l" name="business_line">@foreach (\App\Models\Order::LINES as $l)<option @selected(old('business_line') === $l)>{{ $l }}</option>@endforeach</select>
            </div>
            <div class="field"><label class="l" for="no-q">Jumlah porsi</label><input id="no-q" name="portions" type="number" min="1" value="{{ old('portions', 10) }}" required></div>
            <div class="field"><label class="l" for="no-d">Tanggal antar</label><input id="no-d" name="delivery_date" type="date" value="{{ old('delivery_date', today()->addDay()->toDateString()) }}" required></div>
            <div class="field"><label class="l" for="no-t">Jam antar</label>
                <select id="no-t" name="delivery_time">@foreach (['10.00', '11.30', '12.00', '17.00'] as $t)<option @selected(old('delivery_time', '11.30') === $t)>{{ $t }}</option>@endforeach</select>
            </div>
            <div class="field full"><label class="l" for="no-a">Alamat</label><input id="no-a" name="address" type="text" value="{{ old('address') }}" placeholder="Alamat lengkap pengantaran"></div>
            <div class="field full"><label class="l" for="no-n">Catatan & preferensi</label><textarea id="no-n" name="notes" rows="2" placeholder="Contoh: 5 porsi tidak pedas, alergi kacang">{{ old('notes') }}</textarea></div>
            <div class="field full"><label class="l" for="no-p">Pembayaran</label>
                <select id="no-p" name="payment">@foreach (\App\Models\Order::PAYMENT_OPTIONS as $k => $v)<option value="{{ $k }}" @selected(old('payment') === $k)>{{ $v }}</option>@endforeach</select>
            </div>
        </div>
    </form>
    <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="noForm" type="submit">Simpan pesanan</button></div>
</template>
