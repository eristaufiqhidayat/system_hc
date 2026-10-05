@extends('layouts.print')
@section('title', 'Label kemasan')

@section('content')
    <h1 style="margin-bottom:16px">Label kemasan · {{ day_id($date) }}, {{ date_id($date, true) }}</h1>
    @php $hospitalMenu = $items->where('hospital_portions', '>', 0)->pluck('menu_name')->take(3)->implode(' · '); @endphp
    <div class="labels">
        @foreach ($contracts as $c)
            @forelse ($c->diets as $d)
                <div class="label-card">
                    <b style="font-size:18px">{{ $c->customer->name }} · Kamar {{ $d->room }}</b>
                    <span>{{ $hospitalMenu }}</span>
                    <b style="color:var(--red-ink)">{{ mb_strtoupper(str_starts_with($d->diet_type, 'Diet') ? $d->diet_type : 'Diet '.$d->diet_type) }}{{ $d->note ? ' · '.mb_strtolower($d->note) : '' }}</b>
                </div>
            @empty
                <div class="label-card">
                    <b style="font-size:18px">{{ $c->customer->name }} · {{ $c->customer->area }}</b>
                    <span>{{ $c->daily_portions }} box · {{ $c->delivery_info }}</span>
                </div>
            @endforelse
        @endforeach
    </div>
@endsection
