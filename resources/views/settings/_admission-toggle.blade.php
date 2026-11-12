<div class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-line px-4 py-3">
    <div>
        <p class="font-medium text-head">{{ __('Admissions') }}</p>
        <span
            class="mt-1 inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $admissionOn ? 'bg-ok/15 text-ok' : 'bg-warn/15 text-warn' }}">
            {{ $admissionOn ? __('Open') : __('Closed') }}
        </span>
    </div>
    <form method="POST" action="{{ route('settings.admission.toggle') }}">
        @csrf
        <input type="hidden" name="is_on" value="{{ $admissionOn ? '0' : '1' }}">
        <x-button :variant="$admissionOn ? 's' : 'p'">
            {{ $admissionOn ? __('Turn off') : __('Turn on') }}
        </x-button>
    </form>
</div>
