<div class="rounded-lg bg-card shadow-card p-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="text-head font-medium">Public Registration</h3>
        <p class="text-mute text-sm mt-1">
            বর্তমান অবস্থা:
            <span
                class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $registrationEnabled ? 'bg-ok/15 text-ok' : 'bg-bad/15 text-bad' }}">{{ $registrationEnabled ? 'চালু' : 'বন্ধ' }}</span>
        </p>
    </div>
    <form method="POST" action="{{ route('settings.registration') }}">
        @csrf
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 {{ $registrationEnabled ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-primary text-white hover:bg-[#2862d8]' }}">
            {{ $registrationEnabled ? 'বন্ধ করুন' : 'চালু করুন' }}
        </button>
    </form>
</div>
