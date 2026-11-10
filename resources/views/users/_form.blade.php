@php $isSelf = $user && $user->is(auth()->user()); @endphp

<x-field label="Name" name="name" :value="$user?->name" required />
<x-field label="Email" name="email" type="email" :value="$user?->email" required />
<x-field label="Phone" name="phone" :value="$user?->phone" />

<div class="mb-4">
    <label for="role" class="fl">Role</label>
    <select id="role" name="role" class="fc" @disabled($isSelf)>
        <option value="">-- নির্বাচন করুন --</option>
        @foreach ($roles as $role)
            <option value="{{ $role }}" @selected(old('role', $user?->roles->first()?->name) === $role)>{{ $role }}</option>
        @endforeach
    </select>
    @if ($isSelf)
        <p class="muted text-xs mt-1">নিজের role নিজে বদলানো যাবে না।</p>
    @endif
</div>
