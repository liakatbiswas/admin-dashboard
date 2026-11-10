    <x-app-layout title="Users" :crumbs="['Users' => null]">

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-72">
                    <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-mute" />
                    <input type="search" name="q" value="{{ request('q') }}"
                        placeholder="Search name, email or phone"
                        class="w-full rounded-md border border-line bg-card py-2 pl-10 pr-3 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45">
                </div>
                <select name="status" class="rounded-md border border-line bg-card px-3 py-2 text-sm text-head"
                    onchange="this.form.submit()">
                    @foreach (['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Deleted'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-button variant="s">Search</x-button>
            </form>
            <a href="{{ route('users.create') }}"
                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-[#2862d8]">
                <x-icon name="plus" class="size-4" /> Invite user
            </a>
        </div>

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
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
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
                                    <span
                                        class="rounded bg-bad/15 px-2 py-0.5 text-xs font-medium text-bad">Deleted</span>
                                @elseif ($user->is_active)
                                    <span class="rounded bg-ok/15 px-2 py-0.5 text-xs font-medium text-ok">Active</span>
                                @else
                                    <span
                                        class="rounded bg-warn/15 px-2 py-0.5 text-xs font-medium text-warn">Inactive</span>
                                @endif
                            </td>
                            <td class="text-mute">{{ $user->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    @if ($user->trashed())
                                        @if (Route::has('users.restore'))
                                            <form method="POST" action="{{ route('users.restore', $user) }}">
                                                @csrf
                                                <x-button variant="ps" size="sm">Restore</x-button>
                                            </form>
                                        @endif
                                    @else
                                        @if (Route::has('users.invite'))
                                            <form method="POST" action="{{ route('users.invite', $user) }}">
                                                @csrf
                                                <x-button variant="s" size="sm">Resend invite</x-button>
                                            </form>
                                        @endif
                                        <a href="{{ route('users.edit', $user) }}"
                                            class="inline-flex items-center gap-1 rounded-md bg-soft px-3 py-1 text-xs font-medium text-head hover:bg-line">
                                            <x-icon name="edit" class="size-3.5" /> Edit
                                        </a>
                                        @if (auth()->id() !== $user->id)
                                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                                data-confirm="{{ $user->name }} কে মুছে ফেলবেন?">
                                                @csrf
                                                @method('DELETE')
                                                <x-button variant="bad" size="sm">
                                                    <x-icon name="trash" class="size-3.5" /> Delete
                                                </x-button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-mute">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <div class="mt-6">{{ $users->links() }}</div>
    </x-app-layout>
