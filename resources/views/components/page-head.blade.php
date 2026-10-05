@props(['eyebrow' => '', 'title'])
<div class="head">
    <div>
        <div class="eyebrow">{{ $eyebrow }}</div>
        <h1>{{ $title }}</h1>
    </div>
    @isset($actions)
        <div class="actions">{{ $actions }}</div>
    @endisset
</div>
