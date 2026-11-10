@props(['user'])
@if ($user->avatar)
    <img src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }}"
        {{ $attributes->merge(['class' => 'rounded-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'rounded-full flex items-center justify-center font-semibold text-white']) }}
        style="background:var(--pr);width:36px;height:36px">
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    </div>
@endif
