<x-app-layout title="Temporary password" :crumbs="['Users' => route('users.index'), 'Temporary password' => null]">
    <x-card class="max-w-3xl space-y-4 p-6">
        <h2 class="text-lg font-medium text-head">{{ __('Temporary password') }}</h2>
        <p>{{ __('Account') }}: {{ $user->name }} ({{ $user->email ?? $user->student_id ?? $user->phone }})</p>
        <p class="text-mute">{{ __('This password is shown once. Give it to the user privately before leaving this page.') }}</p>
        <p class="select-all break-all rounded-md bg-soft p-4 font-mono text-lg text-head">{{ $temporaryPassword }}</p>
        <p>{{ __('Expires at') }}: {{ $user->temporary_password_expires_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }} ({{ __('Bangladesh time') }})</p>
        <p class="text-mute">{{ __('The user must change this password after signing in.') }}</p>
        <a href="{{ route('users.index') }}" class="inline-flex rounded-md bg-primary px-4 py-2 text-white">{{ __('Back to users') }}</a>
    </x-card>
</x-app-layout>
