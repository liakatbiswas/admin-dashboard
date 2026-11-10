{{-- Shared by create and edit. Expects $groups (permissions grouped by prefix), $selected (names). --}}
@php
    $editing = isset($role) && $role->exists;
    $locked = $isProtected ?? false;
@endphp

<div class="max-w-md">
    <x-field label="Role name" name="name" :value="$editing ? $role->name : null" placeholder="content-editor" :readonly="$locked" required />
    <p class="mt-1 text-xs text-mute">শুধু ছোট হাতের ইংরেজি অক্ষর, সংখ্যা, - ও _ চলবে।</p>
</div>

<div class="mt-6 space-y-5">
    <p class="text-[15px] font-medium text-head">Permissions</p>

    @forelse ($groups as $group => $permissions)
        <fieldset class="rounded-md border border-line p-4">
            <legend class="px-1 text-sm font-medium text-head">{{ ucfirst($group) }}</legend>
            <label class="mb-3 mt-1 flex items-center gap-2 text-mute">
                <input type="checkbox" data-check-all class="size-4 accent-primary"> Select all in {{ $group }}
            </label>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($permissions as $permission)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                            class="perm size-4 accent-primary" @checked(in_array($permission->name, $selected ?? [], true))>
                        {{ ucwords(str_replace(['.', '_'], ' ', $permission->name)) }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @empty
        <p class="text-mute">আপনার কাছে কোনো permission নেই।</p>
    @endforelse

    @error('permissions.*')
        <p class="text-sm text-bad">{{ $message }}</p>
    @enderror
</div>
