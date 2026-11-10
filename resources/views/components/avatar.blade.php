@props(['user', 'size' => 'size-9'])
@php
    $src = $user->avatar
        ? \Illuminate\Support\Facades\Storage::url($user->avatar)
        : 'https://i.pravatar.cc/80?img=' . (($user->id % 70) + 1);
@endphp
<img src="{{ $src }}" alt="{{ $user->name }}" loading="lazy"
    {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover']) }}>
