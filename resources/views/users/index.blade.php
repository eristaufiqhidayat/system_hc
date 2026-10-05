@extends('layouts.app')
@section('title', 'Pengguna & Akses')

@section('content')
    <x-page-head eyebrow="Setiap orang hanya melihat yang dia perlukan ·" title="Pengguna & Akses">
        <x-slot:actions><button class="btn pri" type="button" data-modal="tpl-user"><x-icon name="plus"/>Tambah pengguna</button></x-slot:actions>
    </x-page-head>

    <div class="g12" style="align-items:start">
        <div class="card"><h2>Tim · {{ $users->count() }} orang</h2>
            @foreach ($users as $u)
                <div class="list-item">
                    <div class="av">{{ $u->initials }}</div>
                    <div class="col grow"><b>{{ $u->name }}</b><span class="sub">{{ $u->email }}</span></div>
                    <form method="POST" action="{{ route('users.update', $u) }}" class="row">
                        @csrf @method('PATCH')
                        <input type="hidden" name="is_active" value="{{ $u->is_active ? 1 : 0 }}">
                        <label class="sr-only" for="role-{{ $u->id }}">Peran {{ $u->name }}</label>
                        <select id="role-{{ $u->id }}" name="role" class="sm" data-autosubmit>
                            @foreach ($roles as $k => $v)<option value="{{ $k }}" @selected($u->role === $k)>{{ $v }}</option>@endforeach
                        </select>
                    </form>
                </div>
            @endforeach
        </div>
        <div class="card pad0">
            <div style="padding:18px 20px"><h2>Hak akses per peran</h2></div>
            <div class="tablewrap"><table class="perm">
                <thead><tr><th>Akses</th>@foreach ($roles as $r)<th style="text-align:center">{{ $r }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($permissions as $p)
                        <tr><td class="strong">{{ $p['label'] }}</td>
                            @foreach (array_keys($roles) as $r)
                                <td>@if (in_array($r, $p['roles'], true))<span class="yes" aria-label="Ya"><x-icon name="check" size="18"/></span>@else<span class="no" aria-label="Tidak">—</span>@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
    </div>
@endsection

@push('templates')
    <template id="tpl-user">
        <div class="mh"><h2 id="modalTitle" style="font-size:18px">Tambah pengguna</h2><button class="x" data-close aria-label="Tutup"><x-icon name="x"/></button></div>
        <form class="mb" id="usForm" method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field"><label class="l" for="us-n">Nama</label><input id="us-n" name="name" type="text" required></div>
                <div class="field"><label class="l" for="us-r">Peran</label><select id="us-r" name="role">@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected($k === 'admin')>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label class="l" for="us-e">Email</label><input id="us-e" name="email" type="text" inputmode="email" required></div>
                <div class="field"><label class="l" for="us-p">WhatsApp</label><input id="us-p" name="phone" type="tel"></div>
            </div>
            <p class="sub">Kata sandi sementara dikirim via WhatsApp bersama tautan login.</p>
        </form>
        <div class="mf"><button class="btn" type="button" data-close>Batal</button><button class="btn pri" form="usForm" type="submit">Kirim undangan</button></div>
    </template>
@endpush
