{{-- Usage: <x-field label="Email" name="email" type="email" :value="$user->email" bag="default" /> --}}
@props(['label', 'name', 'type' => 'text', 'value' => null, 'bag' => 'default', 'options' => [], 'rows' => 3])
@php
    $errorBag = $errors->getBag($bag);
    $id = $attributes->get('id', $name);
    $inputClass =
        'w-full rounded-md border bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft ' .
        ($errorBag->has($name) ? 'border-bad' : 'border-line');
    $current = old($name, $value);
@endphp
<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-head">{{ __($label) }}</label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="{{ $inputClass }}"
            {{ $attributes->except('id') }}>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="{{ $inputClass }}"
            {{ $attributes->except('id') }}>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ __($optionLabel) }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}"
            value="{{ $type === 'password' ? '' : $current }}" class="{{ $inputClass }}"
            {{ $attributes->except('id') }}>
    @endif

    @error($name, $bag)
        <p class="mt-1 text-sm text-bad">{{ $message }}</p>
    @enderror
</div>
