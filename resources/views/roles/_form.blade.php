@php
    $labels = config('permissions.permissions');
    $selected = old('permissions', $selected);
    $locked = isset($isProtected) && $isProtected;
@endphp

<div class="mb-5">
    <label for="name" class="fl">Role Name</label>
    <input id="name" name="name" value="{{ old('name', $role->name ?? '') }}" class="fc"
        placeholder="content-editor" @readonly($locked) required>
    @if ($locked)
        <p class="muted text-xs mt-1">এই role-এর নাম বদলানো যাবে না, শুধু permission বদলানো যাবে।</p>
    @endif
</div>

<h3 class="hd font-medium mb-3">Permissions</h3>

@foreach ($groups as $group => $permissions)
    <fieldset class="it mb-3">
        <legend class="px-2 text-sm font-medium hd">{{ config("permissions.groups.$group", $group) }}</legend>

        <label class="flex items-center gap-2 text-xs muted mb-3">
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
