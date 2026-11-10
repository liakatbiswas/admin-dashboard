<x-guest-layout title="Verify email">
    <h4 class="text-center text-xl font-medium text-head">Verify your email</h4>
    <p class="mb-6 mt-1 text-center text-mute">We sent a verification link to your email. Click it to activate your
        account. Did not get it? Request another below.</p>

    @if (session('status') === 'verification-link-sent')
        <x-alert>A new verification link has been sent to your email address.</x-alert>
    @endif

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button class="w-full">Resend verification email</x-button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button variant="s" class="w-full">Log out</x-button>
        </form>
    </div>
</x-guest-layout>
