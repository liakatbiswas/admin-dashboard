<x-app-layout title="Role Edit" :crumbs="['Roles' => route('roles.index'), 'Edit Role' => null]">
    <x-card class="p-6">
        @if ($isProtected)
            <p class="mb-5 rounded-md bg-warn/15 px-4 py-3 text-sm text-warn">{{ __('This is a protected role. The name cannot be changed, but the permissions can be modified.') }} </p>
        @endif
        <form method="POST" action="{{ route('roles.update', $role) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('roles._form', [
                'groups' => $groups,
                'selected' => $selected,
                'role' => $role,
                'isProtected' => $isProtected,
            ])
            <div class="flex justify-end gap-2">
                <a href="{{ route('roles.index') }}"
                    class="inline-flex items-center rounded-md bg-soft px-4 py-2 text-sm font-medium text-head hover:bg-line">{{ __('Cancel') }}</a>
                <x-button>{{ __('Save role') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
