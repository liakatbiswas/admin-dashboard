<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($siteSettings->site_description)
        <meta name="description" content="{{ $siteSettings->site_description }}">
    @endif

    <title>{{ $siteSettings->site_title }} | {{ __($title) }}</title>

    <link rel="icon"
        href="{{ $siteSettings->favicon_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon_path)
            : asset('assets/images/logo/favicon.ico') }}">


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
    @include('layouts.app-parts.sidebar')

    {{-- Main column --}}
    <div id="wrap"
        class="flex min-h-screen flex-col transition-[margin] duration-200 lg:ml-[250px] lg:[.sm-nav_&]:ml-[70px]">
        @include('layouts.app-parts.header')

        <main class="min-w-0 flex-1 p-4 sm:p-6">

            <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-lg font-medium text-head">{{ __($title) }}</h1>
                <nav class="flex flex-wrap items-center gap-2 text-mute" aria-label="{{ __('Breadcrumb') }}">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary">{{ __('Dashboard') }}</a>

                    @foreach ($crumbs as $label => $url)
                        <x-icon name="chev" class="size-4" />
                        @if ($url && !$loop->last)
                            <a href="{{ $url }}" class="hover:text-primary">{{ __($label) }}</a>
                        @else
                            <span class="text-head">{{ __($label) }}</span>
                        @endif
                    @endforeach
                </nav>
            </div>

            @foreach (['success', 'status', 'error', 'warning'] as $key)
                @if (session($key))
                    <x-alert :tone="$key === 'error' ? 'bad' : ($key === 'warning' ? 'warn' : 'ok')">{{ session($key) }}</x-alert>
                @endif
            @endforeach

            {{ $slot }}

        </main>

        @include('layouts.app-parts.footer')
    </div>

    {{-- Back to top --}}
    <button id="up" type="button" aria-label="{{ __('Back to top') }}"
        class="fixed bottom-5 right-14 z-[25] grid size-10 cursor-pointer place-items-center rounded-full bg-primary text-white opacity-0 pointer-events-none transition-opacity">
        <x-icon name="up" />
    </button>


</body>

</html>
