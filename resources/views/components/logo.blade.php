@php
    $lightLogo = $siteSettings->light_logo_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->light_logo_path)
        : asset('assets/images/logo/logo-dark.png');
    $darkLogo = $siteSettings->dark_logo_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->dark_logo_path)
        : $lightLogo;
@endphp

<div class="app-menu bg-white dark:bg-slate-800 border-e border-slate-200 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 shadow-xs transition-colors duration-200">
    <a href="{{ route('dashboard') }}" class="logo-box">
        <div class="logo-light dark:hidden">
            <img src="{{ $lightLogo }}" class="logo-sm hidden lg:block" alt="{{ $siteSettings->site_title }}">
            <img src="{{ $lightLogo }}" class="logo-sm block lg:hidden" alt="{{ $siteSettings->site_title }}">
        </div>
        <div class="hidden dark:block">
            <img src="{{ $darkLogo }}" class="logo-sm hidden lg:block" alt="{{ $siteSettings->site_title }}">
            <img src="{{ $darkLogo }}" class="logo-sm block lg:hidden" alt="{{ $siteSettings->site_title }}">
        </div>
    </a>
</div>
