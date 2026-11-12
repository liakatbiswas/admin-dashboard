<x-guest-layout title="Registration Status">
    @if (!\App\Models\AdmissionOnOff::isOn())
        <div class="text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-warn/15 text-warn">
                {{-- <x-icon name="lock" class="size-7" /> --}}
                <x-icon name="lock" :size="72" />
            </span>
            <h4 class="mt-4 text-xl font-medium text-head">{{ __('Registration is currently closed') }}</h4>
            <p class="mx-auto mt-2 max-w-xs text-mute">{{ __('New accounts are not currently available. Please contact the administrator if you need an account.') }} </p>

            <div class="mt-6">
                <a href="{{ route('login') }}"
                    class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-[#2862d8]">{{ __('Log in') }} </a>
            </div>
        </div>
    @else
        <h4 class="text-center text-xl font-medium text-head">{{ __('Create an account') }}</h4>
        <p class="mb-6 mt-1 text-center text-mute">{{ __('New accounts start with the student role.') }}</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <x-field label="Full name" name="name" autocomplete="name" placeholder="{{ __('Your full name') }}" required
                autofocus />
            <x-field label="Email address" name="email" type="email" autocomplete="username"
                placeholder="you@example.com" required />
            <x-field label="Password" name="password" type="password" autocomplete="new-password" required />
            <x-field label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password"
                required />

            <x-button class="w-full">{{ __('Register') }}</x-button>
        </form>
        <p class="mt-6 text-center text-sm">{{ __('Already have an account?') }} <a href="{{ route('login') }}"
                class="text-primary">{{ __('Log in') }}</a></p>
    @endif
</x-guest-layout>
