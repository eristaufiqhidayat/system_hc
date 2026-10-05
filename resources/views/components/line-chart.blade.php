@props(['labels', 'values', 'unit' => '', 'label' => 'Grafik'])
@php
    $W = 640; $H = 240; $L = 44; $R = 16; $T = 16; $B = 30;
    $n = max(count($values), 2);
    $max = max(50, (int) ceil(max($values ?: [0]) / 50) * 50);
    $x = fn ($i) => $L + $i * ($W - $L - $R) / ($n - 1);
    $y = fn ($v) => $T + ($H - $T - $B) * (1 - $v / $max);
    $path = collect($values)->map(fn ($v, $i) => ($i ? 'L' : 'M').round($x($i), 1).' '.round($y($v), 1))->implode(' ');
    $last = count($values) - 1;
    $step = ($W - $L - $R) / ($n - 1);
@endphp
<div class="chart">
    <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ $label }}">
        @foreach ([0, $max / 2, $max] as $t)
            <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($t) }}" y2="{{ $y($t) }}" stroke="#E2E6E1" stroke-width="1"/>
            <text x="{{ $L - 8 }}" y="{{ $y($t) + 4 }}" text-anchor="end" font-size="11" fill="#5A655E">{{ qty($t) }}</text>
        @endforeach
        @foreach ($labels as $i => $l)
            <text x="{{ $x($i) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="11" fill="#5A655E">{{ $l }}</text>
        @endforeach
        @if ($last >= 0)
            <path d="{{ $path }} L{{ $x($last) }} {{ $y(0) }} L{{ $x(0) }} {{ $y(0) }} Z" fill="#1B7A37" opacity=".08"/>
            <path d="{{ $path }}" fill="none" stroke="#1B7A37" stroke-width="2" stroke-linejoin="round"/>
            @foreach ($values as $i => $v)
                <circle cx="{{ $x($i) }}" cy="{{ $y($v) }}" r="4" fill="#1B7A37" stroke="#fff" stroke-width="2"/>
            @endforeach
            <text x="{{ $x($last) }}" y="{{ $y($values[$last]) - 12 }}" text-anchor="end" font-size="12" font-weight="700" fill="#1C2620">{{ qty($values[$last]) }} {{ $unit }}</text>
            @foreach ($values as $i => $v)
                <rect class="hit" data-tip="{{ $labels[$i] }}: {{ qty($v) }} {{ $unit }}" x="{{ $x($i) - $step / 2 }}" y="{{ $T }}" width="{{ $step }}" height="{{ $H - $T - $B }}" fill="transparent"/>
            @endforeach
        @endif
    </svg>
    <div class="tip"></div>
</div>
