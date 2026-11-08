<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <x-field label="Email" name="email" type="email" required autofocus />
        <x-field label="Password" name="password" type="password" required />
        <label class="flex items-center gap-2 text-sm mb-4">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2">Login</button>
        <div class="flex justify-between text-sm mt-4">
            <a href="{{ route('password.request') }}" class="text-indigo-600">Forgot password?</a>
            <a href="{{ route('register') }}" class="text-indigo-600">Register</a>
        </div>
    </form>
</x-guest-layout>
