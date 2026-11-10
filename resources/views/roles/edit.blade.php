<x-app-layout title="Role Edit" :crumbs="['Edit Role' => null]">

    <x-card class="p-6">
        @if ($isProtected)
            <p class="mb-5 rounded-md bg-warn/15 px-4 py-3 text-sm text-warn">এটি একটি protected role। নাম বদলানো যাবে
                না, তবে permission বদলানো যাবে।</p>
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
                    class="inline-flex items-center rounded-md bg-soft px-4 py-2 text-sm font-medium text-head hover:bg-line">Cancel</a>
                <x-button>Save role</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
