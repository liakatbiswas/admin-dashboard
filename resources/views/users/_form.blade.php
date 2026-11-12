{{-- Shared by create and edit forms. Expects $roles (role names); $user is set only when editing. --}}
@php $editing = isset($user) && $user->exists; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-field label="Full name" name="name" :value="$editing ? $user->name : null" required />
    <x-field label="Email address" name="email" type="email" :value="$editing ? $user->email : null" required />
    <x-field label="Phone" name="phone" :value="$editing ? $user->phone : null" />

    @if ($editing && auth()->id() === $user->id)
        <div>
            <p class="mb-1.5 text-sm font-medium text-head">{{ __('Role') }}</p>
            <p class="text-mute">
                {{ $user->roles->first()?->name ?? 'â€”' }}
                <span class="text-xs">{{ __('(You cannot change your own role.)') }}</span>
            </p>
            <input type="hidden" name="role" value="{{ $user->roles->first()?->name }}">
        </div>
    @else
        <x-field label="Role" name="role" type="select" :value="$editing ? $user->roles->first()?->name : null" :options="$roles->mapWithKeys(fn($r) => [$r => ucfirst($r)])->all()" />
    @endif
</div>

@if (!$editing)
    <p class="mt-4 rounded-md bg-tint px-4 py-3 text-sm text-primary">{{ __('An invitation link will be sent to the user\'s email address. They can use this link to set their own password.') }} </p>
@endif
