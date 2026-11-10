<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <script>
        (function () {
            var h = document.documentElement;
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) h.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<button class="icb" id="th" type="button" aria-label="Light/Dark mode"
    style="position:fixed;top:16px;right:16px;background:var(--cd)">
    <x-icon name="moon" class="i-moon" />
    <x-icon name="sun" class="i-sun" />
</button>

<div class="au">
    <div class="card">
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2 mb-6">
            <x-logo />
            <span class="hd font-semibold text-lg">{{ config('app.name') }}</span>
        </a>

        @if (session('status'))
            <div class="alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert-err">
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
