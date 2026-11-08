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
    </div>
</x-app-layout>
