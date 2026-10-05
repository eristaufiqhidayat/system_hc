@props(['value'])
@if ($value !== null)
    <span class="delta {{ $value >= 0 ? 'up' : 'down' }}">{{ $value >= 0 ? '▲' : '▼' }} {{ str_replace('.', ',', abs($value)) }}%</span>
@endif
