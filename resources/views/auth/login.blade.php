<x-guest-layout title="Sign in">
    <h4 class="text-center text-xl font-medium text-head">Welcome back</h4>
    <p class="mb-6 mt-1 text-center text-mute">Enter your email address and password to access the admin panel.</p>

    @if (session('status'))
        <x-alert>{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-field label="Email address" name="email" type="email" autocomplete="username" placeholder="you@example.com"
            required autofocus />
        <x-field label="Password" name="password" type="password" autocomplete="current-password"
            placeholder="Enter your password" required />

        <div class="flex items-center justify-between gap-2">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="size-4 accent-primary" @checked(old('remember'))>
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-primary">Forgot password?</a>
            @endif
        </div>

        <x-button class="w-full">Log in</x-button>
    </form>
</x-guest-layout>
