<x-app-layout title="Admission applications" :crumbs="['Admission applications' => null]">
    <form method="GET" class="mb-6 flex items-end gap-3"><x-field label="Application number" name="q" :value="request('q')" /><x-button>{{ __('Search') }}</x-button></form>
    <x-card class="overflow-x-auto"><table class="tbl w-full"><thead><tr><th>{{ __('Application number') }}</th><th>{{ __('Name') }}</th><th>{{ __('Academic year') }}</th><th>{{ __('Class') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
    @forelse($applications as $application)<tr><td>{{ $application->number }}</td><td>{{ $application->name_en }}</td><td>{{ $application->academicYear->year }}</td><td>{{ $application->grade }}</td><td>{{ __(ucfirst($application->status)) }} @if($application->published_at) · {{ __('Published') }} @endif</td><td><a href="{{ route('applications.show', $application) }}" class="text-primary">{{ __('Review') }}</a></td></tr>
    @empty <tr><td colspan="6">{{ __('No applications yet.') }}</td></tr>@endforelse
    </tbody></table></x-card><div class="mt-4">{{ $applications->links() }}</div>
</x-app-layout>
