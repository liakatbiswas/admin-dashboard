<x-app-layout title="Users">
<section class="card overflow-hidden">
    <div class="ch flex-wrap">
        <form method="GET" class="flex flex-wrap gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="নাম, ইমেইল বা ফোন" class="fc" style="width:220px">
            <select name="status" class="fc" style="width:auto">
                @foreach (['' => 'সব', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Deleted'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-s">Search</button>
        </form>

        @can('users.create')
            <a href="{{ route('users.create') }}" class="btn btn-p">+ নতুন User</a>
        @endcan
    </div>

    <div class="overflow-x-auto">
        <table class="tbl st">
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
                                <p class="hd font-medium">{{ $u->name }}</p>
                                <p class="muted text-xs">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        @foreach ($u->roles as $r)
                            <span class="bd bd-b mr-1">{{ $r->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if ($u->trashed())
                            <span class="bd bd-r">Deleted</span>
                        @elseif ($u->is_active)
                            <span class="bd bd-g">Active</span>
                        @else
                            <span class="bd bd-y">Inactive</span>
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
                                        <button class="btn btn-sm btn-s">Restore</button>
                                    </form>
                                @endcan
                            @else
                                @can('users.edit')
                                    <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-ps">Edit</a>
                                    @if ($u->isNot(auth()->user()))
                                        <form method="POST" action="{{ route('users.toggle', $u) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-s">{{ $u->is_active ? 'Deactivate' : 'Activate' }}</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('users.create')
                                    <form method="POST" action="{{ route('users.invite', $u) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-s">Resend Invite</button>
                                    </form>
                                @endcan
                                @can('users.delete')
                                    @if ($u->isNot(auth()->user()))
                                        <form method="POST" action="{{ route('users.destroy', $u) }}" data-confirm="{{ $u->name }} মুছে ফেলবেন?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-d">Delete</button>
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
                    <td colspan="4" class="muted text-center py-6">কোনো user পাওয়া যায়নি।</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">{{ $users->links() }}</div>
</section>
</x-app-layout>
