@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    <script>
        (function() {
            var h = document.documentElement;
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) h.classList.add('dark');
                if (localStorage.getItem('sn') === '1') h.classList.add('sm-nav');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/js/app.js'])
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;display=swap">
</head>

<body class="bg-page font-sans text-sm leading-normal text-ink antialiased">
    <div id="ov"
        class="fixed inset-0 z-30 hidden bg-slate-900/50 [html.st-open_&]:block max-lg:[html.sb-open_&]:block"></div>

    <aside id="sb"
        class="fixed inset-y-0 left-0 z-40 flex w-[250px] -translate-x-full flex-col border-r border-line bg-card transition-[width,transform] duration-200 lg:translate-x-0 lg:[html.sm-nav_&]:w-[70px] max-lg:[html.sb-open_&]:translate-x-0">
        <div
            class="flex h-[70px] shrink-0 items-center justify-center gap-2 border-b border-dashed border-line text-[22px] font-bold tracking-[.04em] text-head">
            <x-logo /><span class="lg:[html.sm-nav_&]:hidden">{{ strtoupper(config('app.name')) }}</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 pb-4 pt-2" aria-label="Main">
            <div class="px-3 pb-1.5 pt-[18px] text-xs font-medium tracking-[.08em] text-mute lg:[html.sm-nav_&]:hidden">
                Menu</div>
            <x-nav-link :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
            <x-nav-link :href="route('profile.edit')" icon="user" :active="request()->routeIs('profile.*')">My Profile</x-nav-link>

            @canany(['users.view', 'roles.manage', 'activity.view', 'settings.manage'])
                <div class="px-3 pb-1.5 pt-[18px] text-xs font-medium tracking-[.08em] text-mute lg:[html.sm-nav_&]:hidden">
                    Administration</div>
            @endcanany

            @can('users.view')
                <x-nav-link :href="route('users.index')" icon="users" :active="request()->routeIs('users.*')">Users</x-nav-link>
            @endcan
            @can('roles.manage')
                <x-nav-link :href="route('roles.index')" icon="layout" :active="request()->routeIs('roles.*')">Roles</x-nav-link>
            @endcan
            @can('activity.view')
                <x-nav-link :href="route('activity.index')" icon="chart" :active="request()->routeIs('activity.*')">Activity Log</x-nav-link>
            @endcan
            @can('settings.manage')
                <x-nav-link :href="route('settings.index')" icon="gear" :active="request()->routeIs('settings.*')">Settings</x-nav-link>
            @endcan
        </nav>
    </aside>

    <div id="wrap"
        class="flex min-h-screen flex-col transition-[margin] duration-200 lg:ml-[250px] lg:[html.sm-nav_&]:ml-[70px]">
        <header class="sticky top-0 z-20 flex h-[70px] shrink-0 items-center gap-1 border-b border-line bg-card px-4">
            <button
                class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                id="mt" type="button" aria-label="Toggle menu"><x-icon name="menu" /></button>

            <div class="ml-auto flex items-center gap-0.5 sm:gap-1">
                <button
                    class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                    id="fs" type="button" aria-label="Fullscreen"><x-icon name="expand" /></button>
                <button
                    class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                    id="th" type="button" aria-label="Light/Dark mode">
                    <x-icon name="moon" class="dark:hidden" />
                    <x-icon name="sun" class="hidden dark:block" />
                </button>

                <span class="dd-wrap group relative inline-block">
                    <button
                        class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                        data-dd type="button" aria-label="Account">
                        <x-avatar :user="auth()->user()" class="w-9 h-9" />
                    </button>
                    <div
                        class="dd hidden group-[.open]:block absolute right-0 top-[calc(100%+6px)] z-50 min-w-[170px] rounded-lg border border-line bg-card p-1.5 shadow-xl [&_a]:flex [&_a]:items-center [&_a]:gap-2 [&_a]:whitespace-nowrap [&_a]:rounded-md [&_a]:px-3 [&_a]:py-[7px] [&_a]:text-ink [&_a:hover]:bg-soft [&_hr]:my-1.5 [&_hr]:border-line [&_button]:flex [&_button]:w-full [&_button]:cursor-pointer [&_button]:items-center [&_button]:gap-2 [&_button]:whitespace-nowrap [&_button]:rounded-md [&_button]:px-3 [&_button]:py-[7px] [&_button]:text-left [&_button]:text-ink [&_button:hover]:bg-soft">
                        <div class="px-3 py-2">
                            <p class="text-head font-medium">{{ auth()->user()->name }}</p>
                            <p class="text-mute text-xs">{{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}</p>
                        </div>
                        <hr>
                        <a href="{{ route('profile.edit') }}"><x-icon name="user" />My Profile</a>
                        @can('settings.manage')
                            <a href="{{ route('settings.index') }}"><x-icon name="gear" />Settings</a>
                        @endcan
                        <hr>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><x-icon name="logout" />Log Out</button>
                        </form>
                    </div>
                </span>
            </div>
        </header>

        <main class="p-4 sm:p-6 flex-1 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
                <h1 class="text-lg text-head font-medium">{{ $title }}</h1>
                <nav class="text-mute flex items-center gap-2 text-sm" aria-label="Breadcrumb">
                    <span>{{ config('app.name') }}</span>
                    <x-icon name="chev" class="!w-3.5 !h-3.5" />
                    <span class="text-head">{{ $title }}</span>
                </nav>
            </div>

            @php
                $flash =
                    session('success') ??
                    match (session('status')) {
                        'profile-information-updated' => 'প্রোফাইল আপডেট হয়েছে।',
                        'password-updated' => 'পাসওয়ার্ড আপডেট হয়েছে।',
                        'two-factor-authentication-enabled' => 'এবার QR স্ক্যান করে কোড দিয়ে নিশ্চিত করুন।',
                        'two-factor-authentication-confirmed' => 'Two-factor চালু হয়েছে।',
                        'two-factor-authentication-disabled' => 'Two-factor বন্ধ করা হয়েছে।',
                        'recovery-codes-generated' => 'নতুন recovery code তৈরি হয়েছে।',
                        default => session('status'),
                    };
            @endphp

            @if ($flash)
                <div class="mb-4 rounded-md bg-ok/15 px-4 py-3 text-sm text-ok">{{ $flash }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-bad/15 px-4 py-3 text-sm text-bad [&_p+p]:mt-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{ $slot }}
        </main>

        <footer class="border-t border-line bg-card px-6 py-5 text-center text-[13px] text-mute">{{ now()->year }} ©
            {{ config('app.name') }}</footer>
    </div>

    <button
        class="fixed bottom-5 right-14 z-[25] hidden size-10 cursor-pointer place-items-center rounded-full bg-primary text-white [&.show]:grid"
        id="up" type="button" aria-label="Back to top"><x-icon name="up" /></button>
    <button
        class="fixed bottom-5 right-0 z-[25] grid h-10 w-9 cursor-pointer place-items-center rounded-l-md border border-r-0 border-line bg-card text-mute"
        id="gr" type="button" aria-label="Settings"><x-icon name="gear" /></button>

    <aside id="st"
        class="fixed inset-y-0 right-0 z-[60] w-[300px] max-w-[90vw] translate-x-full bg-card p-5 shadow-[-8px_0_24px_rgba(0,0,0,.15)] transition-transform duration-[250ms] [html.st-open_&]:translate-x-0">
        <div class="flex justify-between items-center mb-5">
            <h5 class="text-[15px] font-medium text-head">Theme Settings</h5>
            <button
                class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                id="stx" type="button" aria-label="Close"><x-icon name="x" /></button>
        </div>
        <p class="mb-1.5 block text-sm font-medium text-head">Theme</p>
        <div class="flex gap-2 mb-5">
            <button
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45"
                type="button" data-th="light">Light</button>
            <button
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45"
                type="button" data-th="dark">Dark</button>
        </div>
        <p class="mb-1.5 block text-sm font-medium text-head">Sidenav View</p>
        <div class="flex gap-2">
            <button
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45"
                type="button" data-sn="0">Default</button>
            <button
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45"
                type="button" data-sn="1">Small</button>
        </div>
    </aside>
</body>

</html>
