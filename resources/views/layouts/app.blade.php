@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    <script>
        (function () {
            var h = document.documentElement;
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) h.classList.add('dark');
                if (localStorage.getItem('sn') === '1') h.classList.add('sm-nav');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div id="ov"></div>

<aside id="sb">
    <div class="sbh"><x-logo /><span class="lbl">{{ strtoupper(config('app.name')) }}</span></div>
    <nav class="sbn" aria-label="Main">
        <div class="sec lbl">Menu</div>
        <x-nav-link :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
        <x-nav-link :href="route('profile.edit')" icon="user" :active="request()->routeIs('profile.*')">My Profile</x-nav-link>

        @canany(['users.view', 'roles.manage', 'activity.view', 'settings.manage'])
            <div class="sec lbl">Administration</div>
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

<div id="wrap">
    <header class="tb">
        <button class="icb" id="mt" type="button" aria-label="Toggle menu"><x-icon name="menu" /></button>

        <div class="ml-auto flex items-center gap-0.5 sm:gap-1">
            <button class="icb" id="fs" type="button" aria-label="Fullscreen"><x-icon name="expand" /></button>
            <button class="icb" id="th" type="button" aria-label="Light/Dark mode">
                <x-icon name="moon" class="i-moon" />
                <x-icon name="sun" class="i-sun" />
            </button>

            <span class="dd-wrap">
                <button class="icb" data-dd type="button" aria-label="Account">
                    <x-avatar :user="auth()->user()" class="w-9 h-9" />
                </button>
                <div class="dd">
                    <div class="px-3 py-2">
                        <p class="hd font-medium">{{ auth()->user()->name }}</p>
                        <p class="muted text-xs">{{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}</p>
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
            <h1 class="hd font-medium" style="font-size:18px">{{ $title }}</h1>
            <nav class="muted flex items-center gap-2 text-sm" aria-label="Breadcrumb">
                <span>{{ config('app.name') }}</span>
                <x-icon name="chev" class="!w-3.5 !h-3.5" />
                <span class="hd">{{ $title }}</span>
            </nav>
        </div>

        @php
            $flash = session('success') ?? match (session('status')) {
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
            <div class="alert-ok">{{ $flash }}</div>
        @endif
        @if ($errors->any())
            <div class="alert-err">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="ft">{{ now()->year }} © {{ config('app.name') }}</footer>
</div>

<button class="fab" id="up" type="button" aria-label="Back to top"><x-icon name="up" /></button>
<button class="gear" id="gr" type="button" aria-label="Settings"><x-icon name="gear" /></button>

<aside id="st">
    <div class="flex justify-between items-center mb-5">
        <h5 class="ct">Theme Settings</h5>
        <button class="icb" id="stx" type="button" aria-label="Close"><x-icon name="x" /></button>
    </div>
    <p class="fl">Theme</p>
    <div class="flex gap-2 mb-5">
        <button class="btn" type="button" data-th="light">Light</button>
        <button class="btn" type="button" data-th="dark">Dark</button>
    </div>
    <p class="fl">Sidenav View</p>
    <div class="flex gap-2">
        <button class="btn" type="button" data-sn="0">Default</button>
        <button class="btn" type="button" data-sn="1">Small</button>
    </div>
</aside>
</body>
</html>
