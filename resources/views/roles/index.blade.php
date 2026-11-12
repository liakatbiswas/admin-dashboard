<x-app-layout title="Roles" :crumbs="['Roles' => null]">

    <div class="mb-6 flex justify-between gap-3">
        <p class="text-mute">{{ __('Roles define what each user can see and do. Super admin always has everything.') }}</p>
        <a href="{{ route('roles.create') }}"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-[#2862d8]">
            <x-icon name="plus" class="size-4" /> {{ __('New role') }} </a>
    </div>

    @error('role')
        <x-alert tone="bad">{{ $message }}</x-alert>
    @enderror

    <x-card class="overflow-x-auto">
        <table
            class="w-full border-collapse [&_th]:whitespace-nowrap [&_th]:bg-soft [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-medium [&_th]:text-head [&_td]:whitespace-nowrap [&_td]:border-t [&_td]:border-line [&_td]:px-4 [&_td]:py-3 [&_tbody_tr:hover]:bg-soft">
            <thead>
                <tr>
                    <th>{{ __('Role') }}</th>
                    <th>{{ __('Users') }}</th>
                    <th>{{ __('Permissions') }}</th>
                    <th class="text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    @php $protected = in_array($role->name, config('permissions.protected_roles', []), true); @endphp
                    <tr>
                        <td>
                            <span class="font-medium text-head">{{ $role->name }}</span>
                            @if ($protected)
                                <span
                                    class="ml-2 rounded bg-warn/15 px-2 py-0.5 text-xs font-medium text-warn">{{ __('Protected') }}</span>
                            @endif
                        </td>
                        <td>{{ $role->users_count }}</td>
                        <td>{{ $role->permissions_count }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($role->name !== 'super-admin')
                                    <a href="{{ route('roles.edit', $role) }}"
                                        class="inline-flex items-center gap-1 rounded-md bg-soft px-3 py-1 text-xs font-medium text-head hover:bg-line">
                                        <x-icon name="edit" class="size-3.5" /> {{ __('Edit') }} </a>
                                @endif
                                @if (!$protected)
                                    <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                        data-confirm="{{ __("Are you sure you want to delete the ':name' role?", ['name' => $role->name]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-button variant="bad" size="sm">
                                            <x-icon name="trash" class="size-3.5" /> {{ __('Delete') }} </x-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-10 text-center text-mute">{{ __('No roles yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-app-layout>
