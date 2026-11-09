@php
    $labels = config('permissions.permissions');
    $selected = old('permissions', $selected);
    $locked = isset($isProtected) && $isProtected;
@endphp

<div class="mb-5">
    <label for="name" class="block text-sm font-medium mb-1">Role Name</label>
    <input id="name" name="name" value="{{ old('name', $role->name ?? '') }}"
        class="w-full border rounded-lg px-3 py-2 {{ $locked ? 'bg-gray-100' : '' }}" placeholder="content-editor"
        @readonly($locked) required>
    @if ($locked)
        <p class="text-xs text-gray-500 mt-1">এই role-এর নাম বদলানো যাবে না, শুধু permission বদলানো যাবে।</p>
    @endif
</div>

<h3 class="font-medium mb-2">Permissions</h3>

@foreach ($groups as $group => $permissions)
    <fieldset data-group class="border rounded-lg p-4 mb-3">
        <legend class="px-2 text-sm font-medium">{{ config("permissions.groups.$group", $group) }}</legend>

        <label class="flex items-center gap-2 text-xs text-gray-500 mb-2">
            <input type="checkbox"
                onclick="this.closest('[data-group]').querySelectorAll('.perm').forEach(c => c.checked = this.checked)">
            এই গ্রুপের সব
        </label>

        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($permissions as $permission)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="perm" name="permissions[]" value="{{ $permission->name }}"
                        @checked(in_array($permission->name, $selected))>
                    {{ $labels[$permission->name] ?? $permission->name }}
                </label>
            @endforeach
        </div>
    </fieldset>
@endforeach
