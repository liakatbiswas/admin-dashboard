@props(['href', 'icon', 'active' => false])
@php
    $base = 'flex cursor-pointer items-center gap-3 rounded-md px-3 py-2.5 text-[14.5px] lg:[.sm-nav_&]:justify-center';
@endphp
<a href="{{ $href }}"
    {{ $attributes->class([$base, $active ? 'bg-tint font-medium text-primary' : 'text-ink hover:bg-soft']) }}>
    <x-icon :name="$icon" />
    <span class="lg:[.sm-nav_&]:hidden">{{ $slot }}</span>
</a>
