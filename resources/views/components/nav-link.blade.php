@props(['active' => false])
<a
    {{ $attributes->merge(['class' => 'block px-3 py-2 rounded-lg ' . ($active ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-800')]) }}>
    {{ $slot }}
</a>
