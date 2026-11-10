<div class="card p-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="hd font-medium">Public Registration</h3>
        <p class="muted text-sm mt-1">
            বর্তমান অবস্থা:
            <span
                class="bd {{ $registrationEnabled ? 'bd-g' : 'bd-r' }}">{{ $registrationEnabled ? 'চালু' : 'বন্ধ' }}</span>
        </p>
    </div>
    <form method="POST" action="{{ route('settings.registration') }}">
        @csrf
        <button class="btn {{ $registrationEnabled ? 'btn-d' : 'btn-p' }}">
            {{ $registrationEnabled ? 'বন্ধ করুন' : 'চালু করুন' }}
        </button>
    </form>
</div>
