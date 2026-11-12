<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class(['flex items-center gap-2']) }}>
    @csrf
    <label class="sr-only" for="language-select">{{ __('Language') }}</label>
    <select id="language-select" name="locale" class="rounded-md border border-line bg-card px-2 py-1.5 text-sm text-head">
        <option value="en" lang="en" @selected(app()->getLocale() === 'en')>English</option>
        <option value="bn" lang="bn" @selected(app()->getLocale() === 'bn')>বাংলা</option>
    </select>
    <button type="submit" class="rounded-md bg-soft px-2 py-1.5 text-sm text-head">{{ __('Apply') }}</button>
</form>
