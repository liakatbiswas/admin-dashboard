<x-app-layout title="My Profile">
    <div class="grid gap-6 max-w-3xl">

        {{-- Profile information --}}
        <section class="card p-6">
            <h2 class="ct mb-4">Profile Information</h2>

            @php $profileErrors = $errors->getBag('updateProfileInformation'); @endphp
            @if ($profileErrors->any())
                <div class="alert-err">
                    @foreach ($profileErrors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('user-profile-information.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4 mb-5">
                    <x-avatar :user="$user" class="w-16 h-16 text-xl" />
                    <input type="file" name="avatar" accept="image/*" class="text-sm muted">
                </div>

                <x-field label="Name" name="name" :value="$user->name" required bag="updateProfileInformation" />
                <x-field label="Email" name="email" type="email" :value="$user->email" required
                    bag="updateProfileInformation" />
                <x-field label="Phone" name="phone" :value="$user->phone" bag="updateProfileInformation" />

                <div class="mb-4">
                    <label for="bio" class="fl">Bio</label>
                    <textarea id="bio" name="bio" rows="3" class="fc">{{ old('bio', $user->bio) }}</textarea>
                </div>
                <div class="mb-5">
                    <label for="address" class="fl">Address</label>
                    <textarea id="address" name="address" rows="2" class="fc">{{ old('address', $user->address) }}</textarea>
                </div>

                <button class="btn btn-p">Save</button>
            </form>
        </section>

        {{-- Password --}}
        <section class="card p-6">
            <h2 class="ct mb-4">Change Password</h2>

            @php $passwordErrors = $errors->getBag('updatePassword'); @endphp
            @if ($passwordErrors->any())
                <div class="alert-err">
                    @foreach ($passwordErrors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('user-password.update') }}">
                @csrf
                @method('PUT')
                <x-field label="Current Password" name="current_password" type="password" required
                    bag="updatePassword" />
                <x-field label="New Password" name="password" type="password" required bag="updatePassword" />
                <x-field label="Confirm Password" name="password_confirmation" type="password" required
                    bag="updatePassword" />
                <button class="btn btn-p">Update Password</button>
            </form>
        </section>

        {{-- Two-factor authentication --}}
        <section class="card p-6">
            @php
                $twoFaErrors = $errors->getBag('confirmTwoFactorAuthentication');
                $twoFaStarted = !is_null($user->two_factor_secret);
                $twoFaConfirmed = !is_null($user->two_factor_confirmed_at);
                $showCodes = in_array(session('status'), [
                    'two-factor-authentication-confirmed',
                    'recovery-codes-generated',
                ]);
            @endphp

            <h2 class="ct mb-1">Two-Factor Authentication</h2>

            @if (!$twoFaStarted)
                <p class="muted mb-4">
                    Authenticator অ্যাপ (Google Authenticator, Authy ইত্যাদি) দিয়ে লগইন আরও নিরাপদ করুন।
                </p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button class="btn btn-p">Enable</button>
                </form>
            @elseif (!$twoFaConfirmed)
                <p class="muted mb-3">
                    অ্যাপ দিয়ে এই QR স্ক্যান করুন, তারপর অ্যাপে দেখানো ৬ ডিজিটের কোড দিয়ে নিশ্চিত করুন।
                </p>

                <div class="mb-3 inline-block bg-white p-2 rounded">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <p class="muted text-xs mb-4 break-all">
                    স্ক্যান না হলে Setup key: <span class="font-mono">{{ decrypt($user->two_factor_secret) }}</span>
                </p>

                @if ($twoFaErrors->any())
                    <div class="alert-err">
                        @foreach ($twoFaErrors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mb-3">
                    @csrf
                    <x-field label="Authentication Code" name="code" inputmode="numeric" autocomplete="one-time-code"
                        required bag="confirmTwoFactorAuthentication" />
                    <button class="btn btn-p">Confirm</button>
                </form>

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-s">Cancel</button>
                </form>
            @else
                <p class="mb-4" style="color:#0e9f6e">Two-factor চালু আছে।</p>

                @if ($showCodes)
                    <div class="alert-ok">
                        <p class="font-medium mb-2">Recovery codes: নিরাপদ জায়গায় সেভ করুন, এগুলো আর দেখানো হবে না।
                        </p>
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
                        <button class="btn btn-s">নতুন Recovery Codes</button>
                    </form>
                    <form method="POST" action="{{ route('two-factor.disable') }}"
                        data-confirm="Two-factor বন্ধ করবেন?">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-d">Disable</button>
                    </form>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
