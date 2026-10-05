@props(['rows', 'unit' => ''])
@php $max = max(array_values($rows) ?: [1]) ?: 1; @endphp
<div class="chart" style="display:flex;flex-direction:column;gap:12px">
    @foreach ($rows as $label => $value)
        <div class="hit" data-tip="{{ $label }}: {{ qty($value) }}{{ $unit }}" style="display:flex;flex-direction:column;gap:5px;padding:2px 0">
            <div class="between"><span>{{ $label }}</span><b>{{ qty($value) }}{{ $unit }}</b></div>
            <div class="bar"><i style="width:{{ round($value / $max * 100, 1) }}%"></i></div>
        </div>
    @endforeach
    <div class="tip"></div>
</div>
