<div class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-line px-4 py-3">
    <div>
        <p class="font-medium text-head">Admissions</p>
        <span
            class="mt-1 inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $admissionOn ? 'bg-ok/15 text-ok' : 'bg-warn/15 text-warn' }}">
            {{ $admissionOn ? 'Open' : 'Closed' }}
        </span>
    </div>
    <form method="POST" action="{{ route('settings.admission.toggle') }}">
        @csrf
        <x-button :variant="$admissionOn ? 's' : 'p'">
            {{ $admissionOn ? 'Turn off' : 'Turn on' }}
        </x-button>
    </form>
</div>
