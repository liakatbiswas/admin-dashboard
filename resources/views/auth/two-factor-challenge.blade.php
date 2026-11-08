<x-guest-layout>
    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf
        <x-field label="Authentication code" name="code" inputmode="numeric" autofocus />
        <p class="text-xs text-gray-500 mb-2">অথবা recovery code দিন:</p>
        <x-field label="Recovery code" name="recovery_code" />
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2">Verify</button>
    </form>
</x-guest-layout>
