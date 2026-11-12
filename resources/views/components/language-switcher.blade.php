@php
    $nextLocale = app()->getLocale() === 'bn' ? 'en' : 'bn';
    $switchLabel = $nextLocale === 'bn' ? 'Switch to Bengali' : 'Switch to English';
@endphp

<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class(['flex items-center']) }}>
    @csrf
    <input type="hidden" name="locale" value="{{ $nextLocale }}">
    <button type="submit" title="{{ __($switchLabel) }}" aria-label="{{ __($switchLabel) }}"
        class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
        <span class="text-xs font-semibold">{{ $nextLocale === 'bn' ? 'বাংলা' : 'English' }}</span>
    </button>
</form>
