<div class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-line px-4 py-3">
    <div>
        <p class="font-medium text-head">Registration</p>
        <span
            class="mt-1 inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $registrationEnabled ? 'bg-ok/15 text-ok' : 'bg-warn/15 text-warn' }}">
            {{ $registrationEnabled ? 'Open' : 'Closed' }}
        </span>
    </div>
    <form method="POST" action="{{ route('settings.registration') }}">
        @csrf
        <x-button :variant="$registrationEnabled ? 's' : 'p'">
            {{ $registrationEnabled ? 'Turn off' : 'Turn on' }}
        </x-button>
    </form>
</div>
