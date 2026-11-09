<x-app-layout title="My Profile">
    <div class="grid gap-6 max-w-3xl">

        {{-- Profile information --}}
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-4">Profile Information</h2>

            @php $profileErrors = $errors->getBag('updateProfileInformation'); @endphp
            @if ($profileErrors->any())
                <div class="mb-4 text-sm text-red-700 bg-red-50 p-3 rounded">
                    @foreach ($profileErrors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('user-profile-information.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4 mb-4">
                    <x-avatar :user="$user" class="h-16 w-16 text-xl" />
                    <input type="file" name="avatar" accept="image/*" class="text-sm">
                </div>

                <x-field label="Name" name="name" :value="$user->name" required />
                <x-field label="Email" name="email" type="email" :value="$user->email" required />
                <x-field label="Phone" name="phone" :value="$user->phone" />

                <div class="mb-4">
                    <label for="bio" class="block text-sm font-medium mb-1">Bio</label>
                    <textarea id="bio" name="bio" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('bio', $user->bio) }}</textarea>
                </div>
                <div class="mb-4">
                    <label for="address" class="block text-sm font-medium mb-1">Address</label>
                    <textarea id="address" name="address" rows="2" class="w-full border rounded-lg px-3 py-2">{{ old('address', $user->address) }}</textarea>
                </div>

                <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">Save</button>
            </form>
        </section>

        {{-- Password --}}
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-4">Change Password</h2>

            @php $passwordErrors = $errors->getBag('updatePassword'); @endphp
            @if ($passwordErrors->any())
                <div class="mb-4 text-sm text-red-700 bg-red-50 p-3 rounded">
                    @foreach ($passwordErrors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('user-password.update') }}">
                @csrf
                @method('PUT')
                <x-field label="Current Password" name="current_password" type="password" required />
                <x-field label="New Password" name="password" type="password" required />
                <x-field label="Confirm Password" name="password_confirmation" type="password" required />
                <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">Update Password</button>
            </form>
        </section>



        {{-- Two-factor authentication --}}
        <section class="bg-white rounded-xl shadow p-6">
            @php
                $twoFaErrors = $errors->getBag('confirmTwoFactorAuthentication');
                $twoFaStarted = !is_null($user->two_factor_secret);
                $twoFaConfirmed = !is_null($user->two_factor_confirmed_at);
                $showCodes = in_array(session('status'), [
                    'two-factor-authentication-confirmed',
                    'recovery-codes-generated',
                ]);
            @endphp

            <h2 class="font-semibold mb-1">Two-Factor Authentication</h2>

            @if (!$twoFaStarted)
                <p class="text-sm text-gray-600 mb-4">
                    Authenticator অ্যাপ (Google Authenticator, Authy ইত্যাদি) দিয়ে লগইন আরও নিরাপদ করুন।
                </p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">Enable</button>
                </form>
            @elseif (!$twoFaConfirmed)
                <p class="text-sm text-gray-600 mb-3">
                    অ্যাপ দিয়ে এই QR স্ক্যান করুন, তারপর অ্যাপে দেখানো ৬ ডিজিটের কোড দিয়ে নিশ্চিত করুন।
                </p>

                <div class="mb-3 inline-block bg-white p-2 border rounded">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <p class="text-xs text-gray-500 mb-4 break-all">
                    স্ক্যান না হলে Setup key: <span class="font-mono">{{ decrypt($user->two_factor_secret) }}</span>
                </p>

                @if ($twoFaErrors->any())
                    <div class="mb-3 text-sm text-red-700 bg-red-50 p-3 rounded">
                        @foreach ($twoFaErrors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mb-3">
                    @csrf
                    <x-field label="Authentication Code" name="code" inputmode="numeric" autocomplete="one-time-code"
                        required />
                    <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">Confirm</button>
                </form>

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf @method('DELETE')
                    <button class="text-sm text-gray-600 underline">Cancel</button>
                </form>
            @else
                <p class="text-sm text-green-700 mb-4">Two-factor চালু আছে।</p>

                @if ($showCodes)
                    <div class="mb-4 bg-amber-50 border border-amber-200 rounded p-4">
                        <p class="text-sm font-medium mb-2">Recovery codes: নিরাপদ জায়গায় সেভ করুন, এগুলো আর দেখানো
                            হবে না।</p>
                        <div class="grid grid-cols-2 gap-1 font-mono text-sm">
                            @foreach ($user->recoveryCodes() as $code)
                                <div>{{ $code }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('two-factor.recovery-codes') }}">
                        @csrf
                        <button class="border rounded-lg px-4 py-2 text-sm">নতুন Recovery Codes</button>
                    </form>
                    <form method="POST" action="{{ route('two-factor.disable') }}"
                        onsubmit="return confirm('Two-factor বন্ধ করবেন?')">
                        @csrf @method('DELETE')
                        <button class="border border-red-300 text-red-600 rounded-lg px-4 py-2 text-sm">Disable</button>
                    </form>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
