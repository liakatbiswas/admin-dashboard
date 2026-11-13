<x-guest-layout :wide="true" title="Selected students">
    <h1 class="mb-4 text-xl font-medium text-head">{{ __('Selected students') }}</h1>
    <form method="GET" class="mb-4 flex items-end gap-2"><x-field label="Academic year" name="year" type="select" :value="request('year')" :options="['' => __('All years')] + $years->pluck('year', 'id')->all()" /><x-button>{{ __('Filter') }}</x-button></form>
    <a href="{{ route('admissions.selected', ['year' => request('year'), 'pdf' => 1]) }}" class="text-primary">{{ __('Download PDF') }}</a>
    <div class="my-4 space-y-3">
    @forelse($applications as $application)
        <div class="rounded-md border border-line p-3"><p class="font-medium">{{ $application->number }}</p><p>{{ $application->name_en }}</p><p>{{ __('Class') }} {{ $application->grade }} · {{ __($application->selected_shift) }}</p></div>
    @empty <p>{{ __('No published selections.') }}</p> @endforelse
    </div>{{ $applications->links() }}
</x-guest-layout>
