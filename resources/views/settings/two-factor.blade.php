<x-app-layout title="Two-factor authentication" :crumbs="['Two-factor authentication' => null]">
    <div class="max-w-4xl">
        <x-card class="h-fit p-6">
            <h2 class="text-[15px] font-medium text-head">{{ __('Two-factor authentication') }}</h2>

            @if (!$user->two_factor_secret)
                <p class="mb-5 mt-1 text-mute">{{ __('Add a second step to your login with an authenticator app.') }}</p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-button>{{ __('Enable 2FA') }}</x-button>
                </form>
            @elseif (!$user->two_factor_confirmed_at)
                <p class="mb-4 mt-1 text-mute">{{ __('Scan this QR code with your app, then enter the code to confirm.') }}</p>
                <div class="mb-4 w-48 rounded-md bg-white p-2">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                    @csrf
                    <x-field label="Authentication code" name="code" inputmode="numeric" autocomplete="one-time-code"
                        bag="confirmTwoFactorAuthentication" required />
                    <x-button>{{ __('Confirm') }}</x-button>
                </form>
            @else
                <p class="mb-4 mt-1 text-mute">{{ __('2FA is on. Store these recovery codes somewhere safe.') }}</p>
                <ul class="mb-5 grid grid-cols-2 gap-2 rounded-md bg-soft p-4 font-mono text-sm text-head">
                    @foreach ($user->recoveryCodes() as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('two-factor.disable') }}"
                    data-confirm="{{ __('Are you sure you want to disable 2FA?') }}">
                    @csrf
                    @method('DELETE')
                    <x-button variant="bad">{{ __('Disable 2FA') }}</x-button>
                </form>
            @endif
        </x-card>
    </div>
</x-app-layout>
