@props(['user'])
@if ($user->avatar)
    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
        {{ $attributes->merge(['class' => 'rounded-full object-cover']) }}>
@else
    <div
        {{ $attributes->merge(['class' => 'rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold']) }}>
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    </div>
@endif
