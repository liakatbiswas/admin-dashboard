{{-- Shared by create and edit forms. Expects $roles (role names); $user is set only when editing. --}}
@php
    $editing = isset($user) && $user->exists;
    $selectedRoles = session()->hasOldInput('roles_submitted')
        ? old('roles', [])
        : ($editing ? $user->getRoleNames()->all() : []);
    $selectedRoles = is_array($selectedRoles) ? $selectedRoles : [];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-field label="Full name" name="name" :value="$editing ? $user->name : null" required />
    <x-field label="Email address" name="email" type="email" :value="$editing ? $user->email : null" />
    <x-field label="Phone" name="phone" :value="$editing ? $user->phone : null" />
</div>

<p class="mt-2 text-sm text-mute">{{ __('Email is required for administrative roles and optional for students and guardians.') }}</p>

<input type="hidden" name="roles_submitted" value="1">
<fieldset class="mt-4 rounded-md border border-line p-4">
    <legend class="px-1 text-sm font-medium text-head">{{ __('Roles') }}</legend>
    <p class="mb-3 text-sm text-mute">{{ __('Select one or more roles for this account.') }}</p>
    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
        @foreach ($roles as $roleName)
            <label class="flex items-center gap-2">
                <input type="checkbox" name="roles[]" value="{{ $roleName }}" class="size-4 accent-primary"
                    @checked(in_array($roleName, $selectedRoles, true))>
                {{ __(ucwords(str_replace('-', ' ', $roleName))) }}
            </label>
        @endforeach
    </div>
    @error('roles')
        <p class="mt-1 text-sm text-bad">{{ $message }}</p>
    @enderror
    @error('roles.*')
        <p class="mt-1 text-sm text-bad">{{ $message }}</p>
    @enderror
</fieldset>

@if (!$editing)
    <p class="mt-4 rounded-md bg-tint px-4 py-3 text-sm text-primary">{{ __('A temporary password valid for 24 hours will be shown after creating this account.') }}</p>
@endif
