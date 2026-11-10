<x-app-layout title="Users">
    <section class="rounded-lg bg-card shadow-card overflow-hidden">
        <div class="flex items-center justify-between gap-2 px-4 py-4 sm:px-6 sm:py-[18px] flex-wrap">
            <form method="GET" class="flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="নাম, ইমেইল বা ফোন"
                    class="w-[220px]! max-w-full w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">
                <select name="status"
                    class="w-auto! w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">
                    @foreach (['' => 'সব', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Deleted'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-soft text-head hover:bg-line">Search</button>
            </form>

            @can('users.create')
                <a href="{{ route('users.create') }}"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">+
                    নতুন User</a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table
                class="w-full border-collapse [&_th]:whitespace-nowrap [&_th]:bg-soft [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-medium [&_th]:text-head [&_td]:whitespace-nowrap [&_td]:border-t [&_td]:border-line [&_td]:px-4 [&_td]:py-3 [&_tbody_tr:hover]:bg-soft [&_tbody_tr:nth-child(even)]:bg-soft">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :user="$u" class="w-9 h-9" />
                                    <div>
                                        <p class="text-head font-medium">{{ $u->name }}</p>
                                        <p class="text-mute text-xs">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @foreach ($u->roles as $r)
                                    <span
                                        class="inline-flex rounded px-2 py-0.5 text-xs font-medium bg-tint text-primary mr-1">{{ $r->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if ($u->trashed())
                                    <span
                                        class="inline-flex rounded px-2 py-0.5 text-xs font-medium bg-bad/15 text-bad">Deleted</span>
                                @elseif ($u->is_active)
                                    <span
                                        class="inline-flex rounded px-2 py-0.5 text-xs font-medium bg-ok/15 text-ok">Active</span>
                                @else
                                    <span
                                        class="inline-flex rounded px-2 py-0.5 text-xs font-medium bg-warn/15 text-warn">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @can('manage', $u)
                                        @if ($u->trashed())
                                            @can('users.delete')
                                                <form method="POST" action="{{ route('users.restore', $u) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 px-3! py-1! text-xs! bg-soft text-head hover:bg-line">Restore</button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('users.edit')
                                                <a href="{{ route('users.edit', $u) }}"
                                                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 px-3! py-1! text-xs! bg-tint text-primary">Edit</a>
                                                @if ($u->isNot(auth()->user()))
                                                    <form method="POST" action="{{ route('users.toggle', $u) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button
                                                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 px-3! py-1! text-xs! bg-soft text-head hover:bg-line">{{ $u->is_active ? 'Deactivate' : 'Activate' }}</button>
                                                    </form>
                                                @endif
                                            @endcan
                                            @can('users.create')
                                                <form method="POST" action="{{ route('users.invite', $u) }}">
                                                    @csrf
                                                    <button
                                                        class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 px-3! py-1! text-xs! bg-soft text-head hover:bg-line">Resend
                                                        Invite</button>
                                                </form>
                                            @endcan
                                            @can('users.delete')
                                                @if ($u->isNot(auth()->user()))
                                                    <form method="POST" action="{{ route('users.destroy', $u) }}"
                                                        data-confirm="{{ $u->name }} মুছে ফেলবেন?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 px-3! py-1! text-xs! bg-red-500 text-white hover:bg-red-600">Delete</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-mute text-center py-6">কোনো user পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $users->links() }}</div>
    </section>
</x-app-layout>
