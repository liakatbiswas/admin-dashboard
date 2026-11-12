<x-app-layout title="My profile" :crumbs="['My profile' => null]">

    <div class="max-w-4xl">
        <div class="space-y-6">
            {{-- Profile information --}}
            <x-card class="p-6">
                <h2 class="text-[15px] font-medium text-head">{{ __('Profile information') }}</h2>
                <p class="mb-5 mt-1 text-mute">{{ __('Update your name, contact details and photo.') }}</p>

                <form method="POST" action="{{ route('user-profile-information.update') }}" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-4">
                        <x-avatar :user="$user" size="size-16" />
                        <div>
                            <label for="avatar" class="mb-1.5 block text-sm font-medium text-head">{{ __('Photo') }}</label>
                            <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                class="text-sm text-mute file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-soft file:px-3 file:py-1.5 file:text-head">
                            @error('avatar', 'updateProfileInformation')
                                <p class="mt-1 text-sm text-bad">{{ $message }}</p>
                            @enderror
                            @if ($user->avatar)
                                <label class="mt-2 flex items-center gap-2 text-sm text-mute">
                                    <input type="checkbox" name="remove_avatar" value="1" class="size-4 accent-primary">{{ __('Remove current photo') }} </label>
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
                        <x-button>{{ __('Save changes') }}</x-button>
                    </div>
                </form>
            </x-card>

        </div>
    </div>
</x-app-layout>
