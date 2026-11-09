@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-800">
    <div class="min-h-screen md:flex">

        <aside id="sidebar" class="hidden md:block w-64 shrink-0 bg-gray-900 text-gray-100">
            <div class="p-4 text-lg font-semibold border-b border-gray-700">{{ config('app.name') }}</div>
            <nav class="p-2 space-y-1 text-sm">

                @can('activity.view')
                    <x-nav-link :href="route('activity.index')" :active="request()->routeIs('activity.*')">Activity Log</x-nav-link>
                @endcan

                @can('users.view')
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">Users</x-nav-link>
                @endcan

                @can('roles.manage')
                    <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">Roles</x-nav-link>
                @endcan

                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">My Profile</x-nav-link>

                @can('settings.manage')
                    <x-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">Settings</x-nav-link>
                @endcan
                {{-- পরের ধাপে Users, Roles, Activity Log লিংক এখানে বসবে --}}
            </nav>
        </aside>

        <div class="flex-1 min-w-0">
            <header class="bg-white shadow-sm px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" class="md:hidden border rounded px-2 py-1"
                        onclick="document.getElementById('sidebar').classList.toggle('hidden')">☰</button>
                    <h1 class="font-semibold">{{ $title }}</h1>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <x-avatar :user="auth()->user()" class="h-9 w-9" />
                    <div class="hidden sm:block leading-tight">
                        <div class="font-medium">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-500">{{ auth()->user()->getRoleNames()->implode(', ') }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-red-600 hover:underline">Logout</button>
                    </form>
                </div>
            </header>

            <main class="p-4 md:p-6">
                {{-- @php
                    $flash =
                        session('success') ??
                        match (session('status')) {
                            'profile-information-updated' => 'প্রোফাইল আপডেট হয়েছে।',
                            'password-updated' => 'পাসওয়ার্ড আপডেট হয়েছে।',
                            default => session('status'),
                        };
                @endphp
                @if ($flash)
                    <div class="mb-4 text-sm text-green-700 bg-green-50 p-3 rounded">{{ $flash }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 text-sm text-red-700 bg-red-50 p-3 rounded">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif --}}

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

                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
