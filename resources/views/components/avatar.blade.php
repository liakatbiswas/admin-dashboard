@props(['user', 'size' => 'size-9'])
@php
    $src = $user->avatar ? \Illuminate\Support\Facades\Storage::url($user->avatar) : null;
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $user->name }}" loading="lazy"
        {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover']) }}>
@else
    <span role="img" aria-label="{{ $user->name }}"
        {{ $attributes->class([$size, 'inline-flex shrink-0 items-center justify-center rounded-full bg-tint text-xs font-semibold text-primary']) }}>
        {{ $initials ?: '?' }}
    </span>
@endif
