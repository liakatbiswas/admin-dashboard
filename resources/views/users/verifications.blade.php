<x-app-layout title="Contact verification" :crumbs="['Contact verification' => null]">
    <p class="mb-6 text-mute">{{ __('Verify only after checking the contact details with the account holder.') }}</p>
    @if ($errors->any())
        <div role="alert" class="mb-4 text-bad">{{ $errors->first() }}</div>
    @endif
    <div class="space-y-4">
        @forelse ($users as $user)
            <x-card class="p-6">
                <h2 class="font-medium text-head">{{ $user->name }}</h2>
                <p class="mb-4 text-sm text-mute">{{ $user->roles->pluck('name')->join(', ') }}</p>
                @foreach (['email' => 'Email', 'phone' => 'Phone'] as $field => $label)
                    <div class="mb-4">
                        <p class="text-head">{{ __($label) }}: {{ $user->{$field} ?? __('Not provided') }}</p>
                        @if ($user->{$field.'_verified_at'})
                            <p class="text-sm text-mute">{{ __('Verified') }}</p>
                        @elseif (filled($user->{$field}))
                            <form method="POST" action="{{ route('contact-verifications.store', $user) }}" class="mt-2 flex flex-wrap items-end gap-3">
                                @csrf
                                <input type="hidden" name="field" value="{{ $field }}">
                                <input type="hidden" name="value" value="{{ $user->{$field} }}">
                                <label class="text-sm text-mute">{{ __('Verification reason') }}
                                    <input name="reason" required maxlength="500" class="mt-1 block rounded-md border border-line bg-card px-3 py-2 text-head">
                                </label>
                                <x-button>{{ __('Mark verified') }}</x-button>
                            </form>
                        @else
                            <p class="text-sm text-mute">{{ __('Ask the user to complete their profile.') }}</p>
                        @endif
                    </div>
                @endforeach
            </x-card>
        @empty
            <p class="text-mute">{{ __('No contacts are waiting for verification.') }}</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-app-layout>
