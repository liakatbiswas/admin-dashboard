<x-guest-layout>
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <x-field label="Password" name="password" type="password" required autofocus />
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2">Confirm</button>
    </form>
</x-guest-layout>
