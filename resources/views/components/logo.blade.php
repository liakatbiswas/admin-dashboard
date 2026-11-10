{{-- <a href="{{ url('/') }}" class="flex items-center gap-2 overflow-hidden">
    <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true">
        <path fill="#5b94f7" d="M12 2l9 5-9 5-9-5z" />
        <path fill="#2f6df0" d="M3 7l9 5v10l-9-5z" />
        <path fill="#1d4fc4" d="M21 7l-9 5v10l9-5z" />
    </svg>
    <b class="text-xl tracking-wide text-head lg:[.sm-nav_&]:hidden">{{ config('app.name') }}</b>
</a> --}}

<div
    class="app-menu bg-white dark:bg-slate-800 border-e border-slate-200 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 shadow-xs transition-colors duration-200">

    <a href="{{ route('dashboard') }}" class="logo-box">
        <div class="logo-light">
            <img src="{{ asset('assets/images/logo/logo-light.png') }}" class="logo-sm" alt="Small logo">
        </div>
    </a>
</div>
