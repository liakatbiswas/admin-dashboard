<x-app-layout title="Roles">
    <div class="bg-white rounded-xl shadow">
        <div class="p-4 flex justify-between items-center">
            <p class="text-sm text-gray-500">Role-এ permission দিয়ে নিয়ন্ত্রণ করুন কে কী করতে পারবে।</p>
            <a href="{{ route('roles.create') }}" class="bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm">+ নতুন
                Role</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th class="p-3">Role</th>
                        <th class="p-3">Users</th>
                        <th class="p-3">Permissions</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($roles as $role)
                        @php
                            $isSuper = $role->name === 'super-admin';
                            $isProtected = in_array($role->name, config('permissions.protected_roles'), true);
                        @endphp
                        <tr>
                            <td class="p-3 font-medium">
                                {{ $role->name }}
                                @if ($isProtected)
                                    <span class="ml-1 text-xs text-gray-400">(system)</span>
                                @endif
                            </td>
                            <td class="p-3">{{ $role->users_count }}</td>
                            <td class="p-3">{{ $isSuper ? 'সব (fixed)' : $role->permissions_count }}</td>
                            <td class="p-3">
                                <div class="flex justify-end gap-3">
                                    @unless ($isSuper)
                                        <a href="{{ route('roles.edit', $role) }}"
                                            class="text-indigo-600 hover:underline">Edit</a>
                                    @endunless

                                    @if (!$isProtected && $role->users_count === 0)
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                            onsubmit="return confirm('{{ $role->name }} role মুছে ফেলবেন?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
