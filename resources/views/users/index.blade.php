@php
    $me = auth()->user();
    $canCreate = $me->can('users.create');
    $canEdit = $me->can('users.edit');
    $canDelete = $me->can('users.delete');
    $hasAnyAction = $canCreate || $canEdit || $canDelete;
@endphp

<x-app-layout title="Users" :crumbs="['Users' => null]">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-mute" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or phone"
                    class="w-full rounded-md border border-line bg-card py-2 pl-10 pr-3 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45">
            </div>
            <select name="status" class="rounded-md border border-line bg-card px-3 py-2 text-sm text-head"
                onchange="this.form.submit()">
                @foreach (['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'invited' => 'Invite pending', 'trashed' => 'Deleted'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-button variant="s">Search</x-button>
        </form>

        @if ($canCreate)
            <a href="{{ route('users.create') }}"
                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-[#2862d8]">
                <x-icon name="plus" class="size-4" /> Invite user
            </a>
        @endif
    </div>

    @error('user')
        <x-alert tone="bad">{{ $message }}</x-alert>
    @enderror

    <x-card class="overflow-x-auto">
        <table
            class="w-full border-collapse [&_th]:whitespace-nowrap [&_th]:bg-soft [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-medium [&_th]:text-head [&_td]:whitespace-nowrap [&_td]:border-t [&_td]:border-line [&_td]:px-4 [&_td]:py-3 [&_tbody_tr:hover]:bg-soft">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Change Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        // Policy runs once per row, and only if the viewer has any action permission
                        $canManage = $hasAnyAction && $me->can('manage', $user);
                        $isSelf = $me->id === $user->id;
                    @endphp
                    <tr class="{{ $user->trashed() ? 'opacity-60' : '' }}">
                        <td>
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$user" />
                                <div>
                                    <p class="font-medium text-head">{{ $user->name }}</p>
                                    <p class="text-xs text-mute">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-mute">{{ $user->phone ?: '—' }}</td>
                        <td>
                            @foreach ($user->roles as $role)
                                <span
                                    class="rounded bg-tint px-2 py-0.5 text-xs font-medium text-primary">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if ($user->trashed())
                                <span class="rounded bg-bad/15 px-2 py-0.5 text-xs font-medium text-bad">Deleted</span>
                            @elseif ($user->invited_at)
                                <span class="rounded bg-info/15 px-2 py-0.5 text-xs font-medium text-info"
                                    title="Invited {{ $user->invited_at->diffForHumans() }}">Invite pending</span>
                            @elseif ($user->is_active)
                                <span class="rounded bg-ok/15 px-2 py-0.5 text-xs font-medium text-ok">Active</span>
                            @else
                                <span
                                    class="rounded bg-warn/15 px-2 py-0.5 text-xs font-medium text-warn">Inactive</span>
                            @endif
                        </td>
                        <td class="text-mute">{{ $user->created_at->format('d M Y') }}</td>

                        {{-- Change status: only for live users, not for yourself --}}
                        <td>
                            @if ($canEdit && $canManage && !$user->trashed() && !$isSelf)
                                <form method="POST" action="{{ route('users.toggle', $user) }}"
                                    @if ($user->is_active) data-confirm="Are you sure you want to deactivate {{ $user->name }}?" @endif>
                                    @csrf
                                    @method('PATCH')
                                    <x-button :variant="$user->is_active ? 's' : 'ps'" size="sm">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-button>
                                </form>
                            @endif
                        </td>

                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($user->trashed())
                                    @if ($canDelete && $canManage)
                                        <form method="POST" action="{{ route('users.restore', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-button variant="ps" size="sm">Restore</x-button>
                                        </form>
                                    @endif
                                @else
                                    @if ($canCreate && $canManage && $user->invited_at)
                                        <form method="POST" action="{{ route('users.invite', $user) }}">
                                            @csrf
                                            <x-button variant="s" size="sm">Resend invite</x-button>
                                        </form>
                                    @endif

                                    @if ($canEdit && $canManage)
                                        <a href="{{ route('users.edit', $user) }}"
                                            class="inline-flex items-center gap-1 rounded-md bg-soft px-3 py-1 text-xs font-medium text-head hover:bg-line">
                                            <x-icon name="edit" class="size-3.5" /> Edit
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                        data-confirm="Are you sure you want to delete {{ $user->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <x-button variant="bad" size="sm">
                                            <x-icon name="trash" class="size-3.5" /> Delete
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-mute">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-6">{{ $users->links() }}</div>
</x-app-layout>
