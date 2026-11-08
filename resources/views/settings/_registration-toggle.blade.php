<div class="bg-white rounded-xl shadow p-5 flex items-center justify-between gap-4">
    <div>
        <h3 class="font-semibold">Public Registration</h3>
        <p class="text-sm text-gray-500">
            বর্তমান অবস্থা:
            <span class="font-medium {{ $registrationEnabled ? 'text-green-600' : 'text-red-600' }}">
                {{ $registrationEnabled ? 'চালু' : 'বন্ধ' }}
            </span>
        </p>
    </div>
    <form method="POST" action="{{ route('settings.registration') }}">
        @csrf
        <button class="px-4 py-2 rounded-lg text-white {{ $registrationEnabled ? 'bg-red-600' : 'bg-green-600' }}">
            {{ $registrationEnabled ? 'বন্ধ করুন' : 'চালু করুন' }}
        </button>
    </form>
</div>
