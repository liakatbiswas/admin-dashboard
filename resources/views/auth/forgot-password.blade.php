<x-guest-layout title="Forgot password">
    <h4 class="text-center text-xl font-medium text-head">Reset password</h4>
    <p class="mb-6 mt-1 text-center text-mute">Enter your email and we will send you a reset link.</p>

    @if (session('status'))
        <x-alert>{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-field label="Email address" name="email" type="email" placeholder="you@example.com" required autofocus />
        <x-button class="w-full">Send reset link</x-button>
    </form>
    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="text-primary">Back to login</a></p>
</x-guest-layout>
