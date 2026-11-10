@props(['label', 'name', 'type' => 'text', 'value' => null, 'bag' => 'default'])
<div class="mb-4">
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-head">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        {{ $attributes->merge(['class' => 'w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5' . ($errors->getBag($bag)->has($name) ? ' bad' : '')]) }}>
</div>
