<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($siteSettings->site_description)
        <meta name="description" content="{{ $siteSettings->site_description }}">
    @endif

    <link rel="icon" href="{{ $siteSettings->favicon_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon_path)
        : asset('assets/images/logo/favicon.ico') }}">

    <title>{{ $siteSettings->site_title }} | {{ __($title) }}</title>

    <script>
        (function() {
            var h = document.documentElement;
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) h.classList.add('dark')
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-page font-sans text-sm leading-normal text-ink antialiased">
    <button id="th" type="button"
        class="fixed right-4 top-4 grid size-10 cursor-pointer place-items-center rounded-full bg-card text-mute hover:text-head"
        aria-label="{{ __('Toggle theme') }}">
        <x-icon name="moon" class="dark:hidden" />
        <x-icon name="sun" class="hidden dark:block" />
    </button>

    <main class="grid min-h-screen place-items-center p-4 sm:p-6">
        <div class="w-full {{ $wide ? 'max-w-4xl' : 'max-w-[440px]' }} rounded-lg bg-card p-6 shadow-card sm:p-8">
            <div class="mb-6 flex justify-center">
                <x-logo />
            </div>

            {{ $slot }}
            <x-language-switcher class="mt-6 justify-center" />

        </div>
    </main>
</body>

</html>
