@props(['user'])
@if ($user->avatar)
    <img src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }}"
        {{ $attributes->merge(['class' => 'size-9 rounded-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'size-9 bg-primary rounded-full flex items-center justify-center font-semibold text-white']) }}>
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    </div>
@endif
