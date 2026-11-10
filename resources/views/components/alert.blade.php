@props(['tone' => 'ok'])
@php
    $tones = [
        'ok' => 'bg-ok/15 text-ok',
        'bad' => 'bg-bad/15 text-bad',
        'info' => 'bg-tint text-primary',
        'warn' => 'bg-warn/15 text-warn',
    ];
@endphp
<div role="alert" {{ $attributes->class(['mb-5 rounded-md px-4 py-3 text-sm', $tones[$tone] ?? $tones['ok']]) }}>
    {{ $slot }}</div>
