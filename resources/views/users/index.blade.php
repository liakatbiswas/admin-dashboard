<x-app-layout title="Users">

    <div class="bg-white rounded-xl shadow">

        <div class="p-4 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
            <form method="GET" class="flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="নাম, ইমেইল বা ফোন"
                    class="border rounded-lg px-3 py-2 text-sm">
                <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                    @foreach (['' => 'সব', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Deleted'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="bg-gray-800 text-white rounded-lg px-4 py-2 text-sm">Search</button>
            </form>

            @can('users.create')
                <a href="{{ route('users.create') }}"
                    class="bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm text-center">+ Add User</a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th class="p-3">User</th>
                        <th class="p-3">Role</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($users as $u)
                        <tr>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <x-avatar :user="$u" class="h-9 w-9" />
                                    <div>
                                        <div class="font-medium">{{ $u->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                @foreach ($u->roles as $r)
                                    <span
                                        class="inline-block bg-indigo-50 text-indigo-700 rounded px-2 py-0.5 text-xs">{{ $r->name }}</span>
                                @endforeach
                            </td>
                            <td class="p-3">
                                @if ($u->trashed())
                                    <span class="text-red-600">Deleted</span>
                                @elseif ($u->is_active)
                                    <span class="text-green-600">Active</span>
                                @else
                                    <span class="text-gray-500">Inactive</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex flex-wrap justify-end gap-3">
                                    @can('manage', $u)
                                        @if ($u->trashed())
                                            @can('users.delete')
                                                <form method="POST" action="{{ route('users.restore', $u) }}">
                                                    @csrf @method('PATCH')
                                                    <button class="text-green-700 hover:underline">Restore</button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('users.edit')
                                                <a href="{{ route('users.edit', $u) }}"
                                                    class="text-indigo-600 hover:underline">Edit</a>
                                                @if ($u->isNot(auth()->user()))
                                                    <form method="POST" action="{{ route('users.toggle', $u) }}">
                                                        @csrf @method('PATCH')
                                                        <button class="text-amber-600 hover:underline">
                                                            {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                            @can('users.create')
                                                <form method="POST" action="{{ route('users.invite', $u) }}">
                                                    @csrf
                                                    <button class="text-gray-600 hover:underline">Resend Invite</button>
                                                </form>
                                            @endcan
                                            @can('users.delete')
                                                @if ($u->isNot(auth()->user()))
                                                    <form method="POST" action="{{ route('users.destroy', $u) }}"
                                                        onsubmit="return confirm('{{ $u->name }}-কে মুছে ফেলবেন?')">
                                                        @csrf @method('DELETE')
                                                        <button class="text-red-600 hover:underline">Delete</button>
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
                            <td colspan="4" class="p-6 text-center text-gray-500">কোনো user পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $users->links() }}</div>
    </div>
</x-app-layout>
