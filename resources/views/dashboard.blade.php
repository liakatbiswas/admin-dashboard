<x-app-layout title="Dashboard">

    <h2 class="text-xl font-semibold mb-4">স্বাগতম, {{ auth()->user()->name }}</h2>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-sm text-gray-500">আপনার Role</div>
            <div class="text-lg font-semibold">{{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}</div>
        </div>

        @can('users.view')
            <div class="bg-white rounded-xl shadow p-5">
                <div class="text-sm text-gray-500">মোট User</div>
                <div class="text-lg font-semibold">{{ $totalUsers }}</div>
            </div>
        @endcan

        @can('settings.manage')
            <div class="md:col-span-2 xl:col-span-3">
                @include('settings._registration-toggle')
            </div>
        @endcan
    </div>

</x-app-layout>
