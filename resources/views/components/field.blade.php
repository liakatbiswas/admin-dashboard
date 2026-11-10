@props(['label', 'name', 'type' => 'text', 'value' => null, 'bag' => 'default'])
<div class="mb-4">
    <label for="{{ $name }}" class="fl">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        {{ $attributes->merge(['class' => 'fc'.($errors->getBag($bag)->has($name) ? ' bad' : '')]) }}>
</div>
