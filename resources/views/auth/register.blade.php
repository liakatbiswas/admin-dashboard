<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <x-field label="Name" name="name" required autofocus />
        <x-field label="Email" name="email" type="email" required />
        <x-field label="Password" name="password" type="password" required />
        <x-field label="Confirm Password" name="password_confirmation" type="password" required />
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2">Register</button>
        <p class="text-sm mt-4 text-center">
            <a href="{{ route('login') }}" class="text-indigo-600">Already registered?</a>
        </p>
    </form>
</x-guest-layout>
