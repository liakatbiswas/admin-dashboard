<x-guest-layout>
    <h4 class="hd font-medium text-center" style="font-size:20px">Welcome back</h4>
    <p class="muted text-center mt-1 mb-6">Enter your email address and password to access the admin panel.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <x-field label="Email address" name="email" type="email" required autofocus />
        <x-field label="Password" name="password" type="password" required />
        <label class="flex items-center gap-2 text-sm mb-5">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button class="btn btn-p w-full">Log in</button>
    </form>

    <div class="flex justify-between text-sm mt-6">
        <a href="{{ route('password.request') }}" class="font-medium" style="color:var(--pr)">Forgot password?</a>
        <a href="{{ route('register') }}" class="font-medium" style="color:var(--pr)">Register</a>
    </div>
</x-guest-layout>
