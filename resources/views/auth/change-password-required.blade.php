<x-guest-layout title="Change password">
    <h1 class="text-xl font-medium text-head">{{ __('Change your password') }}</h1>
    <p class="mb-5 mt-2 text-mute">{{ __('You must change your password before continuing.') }}</p>
    <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <x-field label="Current password" name="current_password" type="password" autocomplete="current-password" bag="updatePassword" required />
        <x-field label="New password" name="password" type="password" autocomplete="new-password" bag="updatePassword" required />
        <x-field label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password" bag="updatePassword" required />
        <x-button class="w-full">{{ __('Update password') }}</x-button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <x-button variant="s" class="w-full">{{ __('Log out') }}</x-button>
    </form>
</x-guest-layout>
