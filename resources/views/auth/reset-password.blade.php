<x-guest-layout title="Set new password">
    <h4 class="text-center text-xl font-medium text-head">Set a new password</h4>
    <p class="mb-6 mt-1 text-center text-mute">Choose a password you have not used before.</p>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <x-field label="Email address" name="email" type="email" :value="request('email')" required />
        <x-field label="New password" name="password" type="password" autocomplete="new-password" required />
        <x-field label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password"
            required />
        <x-button class="w-full">Update password</x-button>
    </form>
</x-guest-layout>
