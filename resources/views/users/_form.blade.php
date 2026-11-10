@php $isSelf = $user && $user->is(auth()->user()); @endphp

<x-field label="Name" name="name" :value="$user?->name" required />
<x-field label="Email" name="email" type="email" :value="$user?->email" required />
<x-field label="Phone" name="phone" :value="$user?->phone" />

<div class="mb-4">
    <label for="role" class="mb-1.5 block text-sm font-medium text-head">Role</label>
    <select id="role" name="role"
        class="w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5"
        @disabled($isSelf)>
        <option value="">-- নির্বাচন করুন --</option>
        @foreach ($roles as $role)
            <option value="{{ $role }}" @selected(old('role', $user?->roles->first()?->name) === $role)>{{ $role }}</option>
        @endforeach
    </select>
    @if ($isSelf)
        <p class="text-mute text-xs mt-1">নিজের role নিজে বদলানো যাবে না।</p>
    @endif
</div>
