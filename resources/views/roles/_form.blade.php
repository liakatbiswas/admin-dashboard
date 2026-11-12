@php
    $editing = isset($role) && $role->exists;
    $locked = $isProtected ?? false;
    $selected = session()->hasOldInput('permissions_submitted') ? old('permissions', []) : ($selected ?? []);
    $selected = is_array($selected) ? $selected : [];
@endphp
<input type="hidden" name="permissions_submitted" value="1">

<div class="max-w-md">
    <x-field label="Role name" name="name" :value="$editing ? $role->name : null" placeholder="content-editor" :readonly="$locked" required />
    <p class="mt-1 text-xs text-mute">
        Only lowercase English letters, numbers, hyphens (-), and underscores (_) are allowed.
    </p>
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
        <p class="text-mute">You do not have any permissions.</p>
    @endforelse

    @error('permissions.*')
        <p class="text-sm text-bad">{{ $message }}</p>
    @enderror
    @error('permissions')
        <p class="text-sm text-bad">{{ $message }}</p>
    @enderror
</div>
