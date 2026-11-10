<x-app-layout title="Dashboard">
    <div class="space-y-6">

        <section class="rounded-lg bg-card shadow-card p-6 flex items-center gap-4">
            <span class="grid size-12 flex-none place-items-center rounded-md rounded-full! bg-tint text-primary"><x-icon
                    name="user" /></span>
            <div>
                <h2 class="text-[17px] text-head font-medium">স্বাগতম, {{ auth()->user()->name }}</h2>
                <p class="text-mute">আপনার role: {{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}</p>
            </div>
        </section>

        <section class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @can('users.view')
                <div class="rounded-lg bg-card shadow-card p-6 flex items-center gap-4">
                    <span class="grid size-12 flex-none place-items-center rounded-md bg-tint text-primary"><x-icon
                            name="users" /></span>
                    <div>
                        <h5 class="text-head font-medium">মোট User</h5>
                        <p class="text-mute">{{ $totalUsers }}</p>
                    </div>
                </div>
                <div class="rounded-lg bg-card shadow-card p-6 flex items-center gap-4">
                    <span class="grid size-12 flex-none place-items-center rounded-md bg-ok/15 text-ok"><x-icon
                            name="check" /></span>
                    <div>
                        <h5 class="text-head font-medium">সক্রিয় User</h5>
                        <p class="text-mute">{{ $activeUsers }}</p>
                    </div>
                </div>
            @endcan
            @can('roles.manage')
                <div class="rounded-lg bg-card shadow-card p-6 flex items-center gap-4">
                    <span class="grid size-12 flex-none place-items-center rounded-md bg-warn/15 text-warn"><x-icon
                            name="layout" /></span>
                    <div>
                        <h5 class="text-head font-medium">মোট Role</h5>
                        <p class="text-mute">{{ $totalRoles }}</p>
                    </div>
                </div>
            @endcan
            <div class="rounded-lg bg-card shadow-card p-6 flex items-center gap-4">
                <span class="grid size-12 flex-none place-items-center rounded-md bg-info/15 text-info"><x-icon
                        name="form" /></span>
                <div>
                    <h5 class="text-head font-medium">Registration</h5>
                    <p class="text-mute">{{ $registrationEnabled ? 'চালু' : 'বন্ধ' }}</p>
                </div>
            </div>
        </section>

        @can('settings.manage')
            @include('settings._registration-toggle')
        @endcan
    </div>
</x-app-layout>
