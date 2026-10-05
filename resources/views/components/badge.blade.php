@props(['tone' => 'gray'])
<span {{ $attributes->merge(['class' => 'badge b-'.$tone]) }}>{{ $slot }}</span>
