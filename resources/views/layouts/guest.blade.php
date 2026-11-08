<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-6">
        <h1 class="text-xl font-semibold mb-4 text-center">{{ config('app.name') }}</h1>

        @if (session('status'))
            <div class="mb-4 text-sm text-green-700 bg-green-50 p-3 rounded">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 text-sm text-red-700 bg-red-50 p-3 rounded">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{ $slot }}
    </div>
</body>

</html>
