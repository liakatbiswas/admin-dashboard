<x-app-layout title="New Role" :crumbs="['Roles' => route('roles.index'), 'Add Role' => null]">

    <x-card class="p-6">
        <form method="POST" action="{{ route('roles.store') }}" class="space-y-6">
            @csrf
            @include('roles._form', ['groups' => $groups, 'selected' => $selected])
            <div class="flex justify-end gap-2">
                <a href="{{ route('roles.index') }}"
                    class="inline-flex items-center rounded-md bg-soft px-4 py-2 text-sm font-medium text-head hover:bg-line">{{ __('Cancel') }}</a>
                <x-button>{{ __('Create role') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
