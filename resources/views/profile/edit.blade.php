<x-app-layout title="My profile" :crumbs="['My profile' => null]">

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Profile information --}}
            <x-card class="p-6">
                <h2 class="text-[15px] font-medium text-head">Profile information</h2>
                <p class="mb-5 mt-1 text-mute">Update your name, contact details and photo.</p>

                <form method="POST" action="{{ route('user-profile-information.update') }}" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-4">
                        <x-avatar :user="$user" size="size-16" />
                        <div>
                            <label for="avatar" class="mb-1.5 block text-sm font-medium text-head">Photo</label>
                            <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                class="text-sm text-mute file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-soft file:px-3 file:py-1.5 file:text-head">
                            @error('avatar', 'updateProfileInformation')
                                <p class="mt-1 text-sm text-bad">{{ $message }}</p>
                            @enderror
                            @if ($user->avatar)
                                <label class="mt-2 flex items-center gap-2 text-sm text-mute">
                                    <input type="checkbox" name="remove_avatar" value="1" class="size-4 accent-primary">
                                    Remove current photo
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-field label="Full name" name="name" :value="$user->name" bag="updateProfileInformation"
                            required />
                        <x-field label="Email address" name="email" type="email" :value="$user->email"
                            bag="updateProfileInformation" required />
                        <x-field label="Current password (required to change email)" name="current_password"
                            type="password" autocomplete="current-password" bag="updateProfileInformation" />
                        <x-field label="Phone" name="phone" :value="$user->phone" bag="updateProfileInformation" />
                        <x-field label="Address" name="address" :value="$user->address" bag="updateProfileInformation" />
                    </div>
                    <x-field label="Bio" name="bio" type="textarea" :value="$user->bio"
                        bag="updateProfileInformation" rows="4" />

                    <div class="flex justify-end">
                        <x-button>Save changes</x-button>
                    </div>
                </form>
            </x-card>

            {{-- Password --}}
            <x-card class="p-6">
                <h2 class="text-[15px] font-medium text-head">Update password</h2>
                <p class="mb-5 mt-1 text-mute">Use a long password you do not use anywhere else.</p>

                <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-field label="Current password" name="current_password" type="password"
                        autocomplete="current-password" bag="updatePassword" required />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-field label="New password" name="password" type="password" autocomplete="new-password"
                            bag="updatePassword" required />
                        <x-field label="Confirm password" name="password_confirmation" type="password"
                            autocomplete="new-password" bag="updatePassword" required />
                    </div>
                    <div class="flex justify-end">
                        <x-button>Update password</x-button>
                    </div>
                </form>
            </x-card>
        </div>

        {{-- Two-factor authentication --}}
        <x-card class="h-fit p-6">
            <h2 class="text-[15px] font-medium text-head">Two-factor authentication</h2>

            @if (!$user->two_factor_secret)
                <p class="mb-5 mt-1 text-mute">Add a second step to your login with an authenticator app.</p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-button>Enable 2FA</x-button>
                </form>
            @elseif (!$user->two_factor_confirmed_at)
                <p class="mb-4 mt-1 text-mute">Scan this QR code with your app, then enter the code to confirm.</p>
                <div class="mb-4 w-48 rounded-md bg-white p-2">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                    @csrf
                    <x-field label="Authentication code" name="code" inputmode="numeric" autocomplete="one-time-code"
                        bag="confirmTwoFactorAuthentication" required />
                    <x-button>Confirm</x-button>
                </form>
            @else
                <p class="mb-4 mt-1 text-mute">2FA is on. Store these recovery codes somewhere safe.</p>
                <ul class="mb-5 grid grid-cols-2 gap-2 rounded-md bg-soft p-4 font-mono text-sm text-head">
                    @foreach ($user->recoveryCodes() as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('two-factor.disable') }}"
                    data-confirm="Are you sure you want to disable 2FA?">
                    @csrf
                    @method('DELETE')
                    <x-button variant="bad">Disable 2FA</x-button>
                </form>
            @endif
        </x-card>
    </div>
</x-app-layout>
