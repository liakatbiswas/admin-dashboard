@php
    $labels = config('permissions.permissions');
    $selected = old('permissions', $selected);
    $locked = isset($isProtected) && $isProtected;
@endphp

<div class="mb-5">
    <label for="name" class="mb-1.5 block text-sm font-medium text-head">Role Name</label>
    <input id="name" name="name" value="{{ old('name', $role->name ?? '') }}"
        class="w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5"
        placeholder="content-editor" @readonly($locked) required>
    @if ($locked)
        <p class="text-mute text-xs mt-1">এই role-এর নাম বদলানো যাবে না, শুধু permission বদলানো যাবে।</p>
    @endif
</div>

<h3 class="text-head font-medium mb-3">Permissions</h3>

@foreach ($groups as $group => $permissions)
    <fieldset class="rounded-md border border-line px-4 py-3 mb-3">
        <legend class="px-2 text-sm font-medium text-head">{{ config("permissions.groups.$group", $group) }}</legend>

        <label class="flex items-center gap-2 text-xs text-mute mb-3">
            <input type="checkbox" data-check-all> এই গ্রুপের সব
        </label>

        <div class="grid gap-3 sm:grid-cols-2">
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
