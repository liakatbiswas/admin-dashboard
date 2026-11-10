@props(['title' => '', 'crumb' => null])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <script>
        (function() {
            var h = document.documentElement;
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) h.classList.add('dark');
                if (localStorage.getItem('sn') === '1') h.classList.add('sm-nav')
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-page font-sans text-sm leading-normal text-ink antialiased">
    {{-- Overlay for mobile sidebar and settings panel --}}
    <div id="ov" class="fixed inset-0 z-30 hidden bg-slate-900/50 max-lg:[.sb-open_&]:block [.st-open_&]:block">
    </div>

    {{-- Sidebar --}}
    <aside id="sb"
        class="fixed inset-y-0 left-0 z-40 flex w-[250px] -translate-x-full flex-col border-r border-line bg-card transition-all duration-200 lg:translate-x-0 lg:[.sm-nav_&]:w-[70px] [.sb-open_&]:translate-x-0">
        <div class="flex h-[70px] shrink-0 items-center px-5 lg:[.sm-nav_&]:justify-center">
            <x-logo />
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-6" aria-label="Main">
            <p class="px-3 pb-1.5 pt-[18px] text-xs font-medium tracking-[.08em] text-mute lg:[.sm-nav_&]:hidden">Menu
            </p>
            <x-nav-link :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
            <x-nav-link :href="route('profile.edit')" icon="user" :active="request()->routeIs('profile.*')">My profile</x-nav-link>

            @canany(['users.manage', 'roles.manage', 'activity.view', 'settings.manage'])
                <p class="px-3 pb-1.5 pt-[18px] text-xs font-medium tracking-[.08em] text-mute lg:[.sm-nav_&]:hidden">
                    Administration</p>
            @endcanany

            @can('users.manage')
                <x-nav-link :href="route('users.index')" icon="users" :active="request()->routeIs('users.*')">Users</x-nav-link>
            @endcan
            @can('roles.manage')
                <x-nav-link :href="route('roles.index')" icon="lock" :active="request()->routeIs('roles.*')">Roles</x-nav-link>
            @endcan
            @can('activity.view')
                <x-nav-link :href="route('activity.index')" icon="clock" :active="request()->routeIs('activity.*')">Activity log</x-nav-link>
            @endcan
            @can('settings.manage')
                <x-nav-link :href="route('settings.index')" icon="gear" :active="request()->routeIs('settings.*')">Settings</x-nav-link>
            @endcan
        </nav>
    </aside>

    {{-- Main column --}}
    <div id="wrap"
        class="flex min-h-screen flex-col transition-[margin] duration-200 lg:ml-[250px] lg:[.sm-nav_&]:ml-[70px]">
        <header class="sticky top-0 z-20 flex h-[70px] items-center gap-1 border-b border-line bg-card px-4">
            <button id="mt" type="button"
                class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                aria-label="Toggle menu">
                <x-icon name="menu" />
            </button>

            <div class="ml-auto flex items-center gap-0.5 sm:gap-1">
                <button id="fs" type="button"
                    class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head max-sm:hidden"
                    aria-label="Fullscreen">
                    <x-icon name="expand" />
                </button>

                <button id="th" type="button"
                    class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                    aria-label="Light/Dark mode">
                    <x-icon name="moon" class="dark:hidden" />
                    <x-icon name="sun" class="hidden dark:block" />
                </button>

                {{-- Account dropdown --}}
                <span data-dw class="group relative inline-block">
                    <button type="button" data-dd class="grid size-10 cursor-pointer place-items-center rounded-full"
                        aria-label="Account">
                        <x-avatar :user="auth()->user()" size="size-9" />
                    </button>
                    <div data-dp
                        class="absolute right-0 top-[calc(100%+6px)] z-50 hidden min-w-[190px] rounded-lg border border-line bg-card p-1.5 shadow-xl group-[.open]:block">
                        <div class="px-3 py-2">
                            <p class="font-medium text-head">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-mute">{{ auth()->user()->email }}</p>
                        </div>
                        <hr class="my-1.5 border-line">
                        <a href="{{ route('profile.edit') }}"
                            class="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-md px-3 py-[7px] text-ink hover:bg-soft">
                            <x-icon name="user" /> My profile
                        </a>
                        @can('settings.manage')
                            <a href="{{ route('settings.index') }}"
                                class="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-md px-3 py-[7px] text-ink hover:bg-soft">
                                <x-icon name="gear" /> Settings
                            </a>
                        @endcan
                        <hr class="my-1.5 border-line">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex w-full cursor-pointer items-center gap-2 whitespace-nowrap rounded-md px-3 py-[7px] text-left text-bad hover:bg-soft">
                                <x-icon name="logout" /> Log out
                            </button>
                        </form>
                    </div>
                </span>


            </div>
        </header>

        <main class="min-w-0 flex-1 p-4 sm:p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-lg font-medium text-head">{{ $title }}</h1>
                <nav class="flex flex-wrap items-center gap-2 text-mute" aria-label="Breadcrumb">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary">Dashboard</a>
                    @if ($crumb)
                        <x-icon name="chev" class="size-4" />
                        <span>{{ $crumb }}</span>
                    @endif
                </nav>
            </div>

            @foreach (['success', 'status', 'error'] as $key)
                @if (session($key))
                    <x-alert :tone="$key === 'error' ? 'bad' : 'ok'">{{ session($key) }}</x-alert>
                @endif
            @endforeach

            {{ $slot }}
        </main>

        <footer class="border-t border-line bg-card px-6 py-5 text-center text-mute">
            © {{ date('Y') }} {{ config('app.name') }}
        </footer>
    </div>

    {{-- Back to top --}}
    <button id="up" type="button" aria-label="Back to top"
        class="fixed bottom-5 right-14 z-[25] grid size-10 cursor-pointer place-items-center rounded-full bg-primary text-white opacity-0 pointer-events-none transition-opacity">
        <x-icon name="up" />
    </button>



</body>

</html>
