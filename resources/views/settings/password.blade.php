<x-app-layout title="Change password" :crumbs="['Settings' => route('settings.index'), 'Change password' => null]">
    <div class="max-w-4xl">
            <x-card class="p-6">
                <h2 class="text-[15px] font-medium text-head">{{ __('Update password') }}</h2>
                <p class="mb-5 mt-1 text-mute">{{ __('Use a long password you do not use anywhere else.') }}</p>

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
                        <x-button>{{ __('Update password') }}</x-button>
                    </div>
                </form>
            </x-card>
    </div>
</x-app-layout>
