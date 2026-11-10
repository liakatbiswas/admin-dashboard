@props(['href', 'icon' => 'box', 'active' => false])
<a href="{{ $href }}" class="nv {{ $active ? 'on' : '' }}">
    <x-icon :name="$icon" />
    <span class="lbl">{{ $slot }}</span>
</a>
