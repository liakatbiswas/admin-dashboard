<x-app-layout title="Dashboard">
    <div class="space-y-6">

        <section class="card p-6 flex items-center gap-4">
            <span class="ib rd c-b"><x-icon name="user" /></span>
            <div>
                <h2 class="hd font-medium" style="font-size:17px">স্বাগতম, {{ auth()->user()->name }}</h2>
                <p class="muted">আপনার role: {{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}</p>
            </div>
        </section>

        <section class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @can('users.view')
                <div class="card p-6 flex items-center gap-4">
                    <span class="ib c-b"><x-icon name="users" /></span>
                    <div>
                        <h5 class="hd font-medium">মোট User</h5>
                        <p class="muted">{{ $totalUsers }}</p>
                    </div>
                </div>
                <div class="card p-6 flex items-center gap-4">
                    <span class="ib c-g"><x-icon name="check" /></span>
                    <div>
                        <h5 class="hd font-medium">সক্রিয় User</h5>
                        <p class="muted">{{ $activeUsers }}</p>
                    </div>
                </div>
            @endcan
            @can('roles.manage')
                <div class="card p-6 flex items-center gap-4">
                    <span class="ib c-y"><x-icon name="layout" /></span>
                    <div>
                        <h5 class="hd font-medium">মোট Role</h5>
                        <p class="muted">{{ $totalRoles }}</p>
                    </div>
                </div>
            @endcan
            <div class="card p-6 flex items-center gap-4">
                <span class="ib c-s"><x-icon name="form" /></span>
                <div>
                    <h5 class="hd font-medium">Registration</h5>
                    <p class="muted">{{ $registrationEnabled ? 'চালু' : 'বন্ধ' }}</p>
                </div>
            </div>
        </section>

        @can('settings.manage')
            @include('settings._registration-toggle')
        @endcan
    </div>
</x-app-layout>
