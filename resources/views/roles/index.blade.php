<x-app-layout title="Roles">
    <section class="rounded-lg bg-card shadow-card overflow-hidden">
        <div class="flex items-center justify-between gap-2 px-4 py-4 sm:px-6 sm:py-[18px] flex-wrap">
            <p class="text-mute">Role-এ permission দিয়ে নিয়ন্ত্রণ করুন কে কী করতে পারবে।</p>
            <a href="{{ route('roles.create') }}"
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">+
                নতুন Role</a>
        </div>

        <div class="overflow-x-auto">
            <table
                class="w-full border-collapse [&_th]:whitespace-nowrap [&_th]:bg-soft [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-medium [&_th]:text-head [&_td]:whitespace-nowrap [&_td]:border-t [&_td]:border-line [&_td]:px-4 [&_td]:py-3 [&_tbody_tr:hover]:bg-soft [&_tbody_tr:nth-child(even)]:bg-soft">
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
                            <td class="font-medium text-head">
                                {{ $role->name }}
                                @if ($isProtected)
                                    <span class="text-mute text-xs ml-1">(system)</span>
                                @endif
                            </td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ $isSuper ? 'সব (fixed)' : $role->permissions_count }}</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    @unless ($isSuper)
                                        <a href="{{ route('roles.edit', $role) }}"
                                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 px-3! py-1! text-xs! bg-tint text-primary">Edit</a>
                                    @endunless

                                    @if (!$isProtected && $role->users_count === 0)
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                            data-confirm="{{ $role->name }} role মুছে ফেলবেন?">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 px-3! py-1! text-xs! bg-red-500 text-white hover:bg-red-600">Delete</button>
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
