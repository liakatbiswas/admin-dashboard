    <x-app-layout title="Invite user" :crumbs="['Users' => route('users.index'), 'Create User' => null]">

        <x-card class="max-w-3xl p-6">
            <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                @csrf
                @include('users._form', ['roles' => $roles])
                <div class="flex justify-end gap-2">
                    <a href="{{ route('users.index') }}"
                        class="inline-flex items-center rounded-md bg-soft px-4 py-2 text-sm font-medium text-head hover:bg-line">Cancel</a>
                    <x-button>Send invitation</x-button>
                </div>
            </form>
        </x-card>
    </x-app-layout>
