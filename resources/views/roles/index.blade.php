<x-app-layout title="Roles">
<section class="card overflow-hidden">
    <div class="ch flex-wrap">
        <p class="muted">Role-এ permission দিয়ে নিয়ন্ত্রণ করুন কে কী করতে পারবে।</p>
        <a href="{{ route('roles.create') }}" class="btn btn-p">+ নতুন Role</a>
    </div>

    <div class="overflow-x-auto">
        <table class="tbl st">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Users</th>
                    <th>Permissions</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($roles as $role)
                @php
                    $isSuper = $role->name === 'super-admin';
                    $isProtected = in_array($role->name, config('permissions.protected_roles'), true);
                @endphp
                <tr>
                    <td class="font-medium hd">
                        {{ $role->name }}
                        @if ($isProtected)
                            <span class="muted text-xs ml-1">(system)</span>
                        @endif
                    </td>
                    <td>{{ $role->users_count }}</td>
                    <td>{{ $isSuper ? 'সব (fixed)' : $role->permissions_count }}</td>
                    <td>
                        <div class="flex justify-end gap-2">
                            @unless ($isSuper)
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-ps">Edit</a>
                            @endunless

                            @if (! $isProtected && $role->users_count === 0)
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" data-confirm="{{ $role->name }} role মুছে ফেলবেন?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-d">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
</x-app-layout>
