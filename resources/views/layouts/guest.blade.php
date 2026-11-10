<!DOCTYPE html>
<html lang="en"
    class="[--bg:#f3f5f9] [--cd:#fff] [--bd:#e6e9ef] [--tx:#505b6e] [--hd:#1e2a3b] [--mt:#7b8699] [--hv:#f1f4f9] [--ac:rgba(48,115,241,.1)] [--sh:0_1px_4px_rgba(15,23,42,.07)] [&.dark]:[--bg:#0e1522] [&.dark]:[--cd:#172131] [&.dark]:[--bd:#263246] [&.dark]:[--tx:#c3cddb] [&.dark]:[--hd:#f2f5f9] [&.dark]:[--mt:#8a97ab] [&.dark]:[--hv:#202c40] [&.dark]:[--ac:rgba(48,115,241,.2)] [&.dark]:[--sh:0_0_0_1px_var(--bd)] motion-reduce:transition-none! motion-reduce:[&_*]:transition-none! scroll-smooth motion-reduce:scroll-auto motion-reduce:[&_*]:scroll-auto [&_[id]]:scroll-mt-[90px] [&_input[type=checkbox]]:size-4 [&_input[type=radio]]:size-4 [&_input]:accent-primary">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <script>
        (function() {
            var h = document.documentElement;
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) h.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/js/app.js'])
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;display=swap">
</head>

<body class="bg-page font-sans text-sm leading-normal text-ink antialiased">
    <button
        class="fixed! top-4 right-4 bg-card relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
        id="th" type="button" aria-label="Light/Dark mode">
        <x-icon name="moon" class="dark:hidden" />
        <x-icon name="sun" class="hidden dark:block" />
    </button>

    <div
        class="grid min-h-screen place-items-center p-4 sm:p-6 [&>div]:w-full [&>div]:max-w-[440px] [&>div]:p-6 sm:[&>div]:p-8">
        <div class="rounded-lg bg-card shadow-card">
            <a href="{{ url('/') }}" class="flex items-center justify-center gap-2 mb-6">
                <x-logo />
                <span class="text-head font-semibold text-lg">{{ config('app.name') }}</span>
            </a>

            @if (session('status'))
                <div class="mb-4 rounded-md bg-ok/15 px-4 py-3 text-sm text-ok">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-bad/15 px-4 py-3 text-sm text-bad [&_p+p]:mt-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</body>

</html>
