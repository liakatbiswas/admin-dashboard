<x-app-layout title="Edit user" :crumbs="['Users' => route('users.index'), 'Edit User' => null]">
    <x-card class="max-w-3xl p-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('users._form', ['roles' => $roles, 'user' => $user])
            <div class="flex justify-end gap-2">
                <a href="{{ route('users.index') }}"
                    class="inline-flex items-center rounded-md bg-soft px-4 py-2 text-sm font-medium text-head hover:bg-line">Cancel</a>
                <x-button>Save changes</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
