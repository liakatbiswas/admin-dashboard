<x-guest-layout title="Create account">
    <h4 class="text-center text-xl font-medium text-head">Create an account</h4>
    <p class="mb-6 mt-1 text-center text-mute">New accounts start with the student role.</p>

    @if (!\App\Models\AdmissionOnOff::isOn())
        <div class="rounded-md bg-warn/15 px-4 py-3 text-center text-warn">
            <p class="font-medium">Registration is currently closed</p>
            <p class="mt-1 text-sm">Please contact the administrator to get an account.</p>
        </div>
        <p class="mt-6 text-center text-sm">Already have an account? <a href="{{ route('login') }}"
                class="text-primary">Log in</a></p>
    @else
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <x-field label="Full name" name="name" autocomplete="name" placeholder="Your full name" required
                autofocus />
            <x-field label="Email address" name="email" type="email" autocomplete="username"
                placeholder="you@example.com" required />
            <x-field label="Password" name="password" type="password" autocomplete="new-password" required />
            <x-field label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password"
                required />

            <x-button class="w-full">Register</x-button>
        </form>
        <p class="mt-6 text-center text-sm">Already have an account? <a href="{{ route('login') }}"
                class="text-primary">Log in</a></p>
    @endif
</x-guest-layout>
