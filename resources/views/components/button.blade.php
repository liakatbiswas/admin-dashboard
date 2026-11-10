@props(['variant' => 'p', 'size' => null, 'type' => 'submit'])
@php
    $base =
        'inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45';
    $variants = [
        'p' => 'bg-primary text-white hover:bg-[#2862d8]',
        's' => 'bg-soft text-head hover:bg-line',
        'ps' => 'bg-tint text-primary',
        'bad' => 'bg-bad text-white hover:opacity-90',
    ];
    $sizes = $size === 'sm' ? 'px-3 py-1 text-xs' : 'px-4 py-2 text-sm';
@endphp
<button type="{{ $type }}"
    {{ $attributes->class([$base, $sizes, $variants[$variant] ?? $variants['p']]) }}>{{ $slot }}</button>
