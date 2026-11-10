@props(['href', 'icon' => 'box', 'active' => false])
<a href="{{ $href }}"
    class="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2.5 text-[14.5px] text-ink hover:bg-soft [&.on]:bg-tint [&.on]:font-medium [&.on]:text-primary lg:[html.sm-nav_&]:justify-center {{ $active ? 'on' : '' }}">
    <x-icon :name="$icon" />
    <span class="lg:[html.sm-nav_&]:hidden">{{ $slot }}</span>
</a>
