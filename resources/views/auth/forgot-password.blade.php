<x-guest-layout>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-field label="Email" name="email" type="email" required autofocus />
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2">Send reset link</button>
    </form>
</x-guest-layout>
