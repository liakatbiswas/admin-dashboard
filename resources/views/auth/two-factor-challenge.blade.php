<x-guest-layout title="Two-factor authentication">
    <h4 class="text-center text-xl font-medium text-head">{{ __('Two-factor authentication') }}</h4>
    <p class="mb-6 mt-1 text-center text-mute">{{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}</p>

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
        @csrf
        <x-field label="Authentication code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
        <x-field label="Or recovery code" name="recovery_code" autocomplete="one-time-code" />
        <x-button class="w-full">{{ __('Verify') }}</x-button>
    </form>
</x-guest-layout>
