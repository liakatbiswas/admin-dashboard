<x-guest-layout title="Confirm password">
    <h4 class="text-center text-xl font-medium text-head">{{ __('Confirm your password') }}</h4>
    <p class="mb-6 mt-1 text-center text-mute">{{ __('This is a secure area. Please confirm your password before continuing.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <x-field label="Password" name="password" type="password" autocomplete="current-password" required autofocus />
        <x-button class="w-full">{{ __('Confirm') }}</x-button>
    </form>
</x-guest-layout>
