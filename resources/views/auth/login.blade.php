<x-guest-layout title="Sign in">
    <h4 class="text-center text-xl font-medium text-head">{{ __('Welcome back') }}</h4>
    <p class="mb-6 mt-1 text-center text-mute">{{ __('Sign in with your email, verified mobile number or Student ID.') }}</p>

    @if (session('status'))
        <x-alert>{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-field label="Email, mobile or Student ID" name="login" type="text" autocomplete="username" placeholder="you@example.com / 01712345678 / NGHS20270600001"
            required autofocus />
        <x-field label="Password" name="password" type="password" autocomplete="current-password"
            placeholder="{{ __('Enter your password') }}" required />

        <div class="flex items-center justify-between gap-2">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="size-4 accent-primary" @checked(old('remember'))>{{ __('Remember me') }} </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-primary">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <x-button class="w-full">{{ __('Log in') }}</x-button>
    </form>

    @if (Route::has('register'))
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-line"></div>
            </div>
            <p class="relative mx-auto w-fit bg-card px-3 text-xs text-mute">{{ __('New here?') }}</p>
        </div>

        @if (\App\Models\AdmissionOnOff::isOn())
            <a href="{{ route('register') }}"
                class="inline-flex w-full items-center justify-center gap-1.5 rounded-md border border-line bg-card px-4 py-2 text-sm font-medium text-head transition hover:bg-soft">
                <x-icon name="user" class="size-4" /> {{ __('Apply for admission') }} </a>
        @else
            <div class="flex w-full cursor-not-allowed items-center justify-center gap-1.5 rounded-md bg-warn/15 px-4 py-2 text-sm font-medium text-warn"
                aria-disabled="true">
                <x-icon name="lock" class="size-4" /> {{ __('Registration Closed') }} </div>
        @endif
    @endif
</x-guest-layout>
