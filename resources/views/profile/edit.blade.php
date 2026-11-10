<x-app-layout title="My Profile">
    <div class="grid gap-6 max-w-3xl">

        {{-- Profile information --}}
        <section class="rounded-lg bg-card shadow-card p-6">
            <h2 class="text-[15px] font-medium text-head mb-4">Profile Information</h2>

            @php $profileErrors = $errors->getBag('updateProfileInformation'); @endphp
            @if ($profileErrors->any())
                <div class="mb-4 rounded-md bg-bad/15 px-4 py-3 text-sm text-bad [&_p+p]:mt-1">
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
                    <input type="file" name="avatar" accept="image/*" class="text-sm text-mute">
                </div>

                <x-field label="Name" name="name" :value="$user->name" required bag="updateProfileInformation" />
                <x-field label="Email" name="email" type="email" :value="$user->email" required
                    bag="updateProfileInformation" />
                <x-field label="Phone" name="phone" :value="$user->phone" bag="updateProfileInformation" />

                <div class="mb-4">
                    <label for="bio" class="mb-1.5 block text-sm font-medium text-head">Bio</label>
                    <textarea id="bio" name="bio" rows="3"
                        class="w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">{{ old('bio', $user->bio) }}</textarea>
                </div>
                <div class="mb-5">
                    <label for="address" class="mb-1.5 block text-sm font-medium text-head">Address</label>
                    <textarea id="address" name="address" rows="2"
                        class="w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">{{ old('address', $user->address) }}</textarea>
                </div>

                <button
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">Save</button>
            </form>
        </section>

        {{-- Password --}}
        <section class="rounded-lg bg-card shadow-card p-6">
            <h2 class="text-[15px] font-medium text-head mb-4">Change Password</h2>

            @php $passwordErrors = $errors->getBag('updatePassword'); @endphp
            @if ($passwordErrors->any())
                <div class="mb-4 rounded-md bg-bad/15 px-4 py-3 text-sm text-bad [&_p+p]:mt-1">
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
                <button
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">Update
                    Password</button>
            </form>
        </section>

        {{-- Two-factor authentication --}}
        <section class="rounded-lg bg-card shadow-card p-6">
            @php
                $twoFaErrors = $errors->getBag('confirmTwoFactorAuthentication');
                $twoFaStarted = !is_null($user->two_factor_secret);
                $twoFaConfirmed = !is_null($user->two_factor_confirmed_at);
                $showCodes = in_array(session('status'), [
                    'two-factor-authentication-confirmed',
                    'recovery-codes-generated',
                ]);
            @endphp

            <h2 class="text-[15px] font-medium text-head mb-1">Two-Factor Authentication</h2>

            @if (!$twoFaStarted)
                <p class="text-mute mb-4">
                    Authenticator অ্যাপ (Google Authenticator, Authy ইত্যাদি) দিয়ে লগইন আরও নিরাপদ করুন।
                </p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button
                        class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">Enable</button>
                </form>
            @elseif (!$twoFaConfirmed)
                <p class="text-mute mb-3">
                    অ্যাপ দিয়ে এই QR স্ক্যান করুন, তারপর অ্যাপে দেখানো ৬ ডিজিটের কোড দিয়ে নিশ্চিত করুন।
                </p>

                <div class="mb-3 inline-block bg-white p-2 rounded">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <p class="text-mute text-xs mb-4 break-all">
                    স্ক্যান না হলে Setup key: <span class="font-mono">{{ decrypt($user->two_factor_secret) }}</span>
                </p>

                @if ($twoFaErrors->any())
                    <div class="mb-4 rounded-md bg-bad/15 px-4 py-3 text-sm text-bad [&_p+p]:mt-1">
                        @foreach ($twoFaErrors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mb-3">
                    @csrf
                    <x-field label="Authentication Code" name="code" inputmode="numeric" autocomplete="one-time-code"
                        required bag="confirmTwoFactorAuthentication" />
                    <button
                        class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">Confirm</button>
                </form>

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button
                        class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-soft text-head hover:bg-line">Cancel</button>
                </form>
            @else
                <p class="text-ok mb-4">Two-factor চালু আছে।</p>

                @if ($showCodes)
                    <div class="mb-4 rounded-md bg-ok/15 px-4 py-3 text-sm text-ok">
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
                        <button
                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-soft text-head hover:bg-line">নতুন
                            Recovery Codes</button>
                    </form>
                    <form method="POST" action="{{ route('two-factor.disable') }}"
                        data-confirm="Two-factor বন্ধ করবেন?">
                        @csrf
                        @method('DELETE')
                        <button
                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-red-500 text-white hover:bg-red-600">Disable</button>
                    </form>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
