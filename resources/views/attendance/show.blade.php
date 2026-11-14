<x-app-layout title="Attendance record" :crumbs="['Attendance' => route('attendance.index'), 'Attendance record' => null]">
    @if($errors->any())<div role="alert" class="mb-4 text-bad">{{ $errors->first() }}</div>@endif
    <x-card class="mb-6 space-y-3 p-6">
        <h1 class="text-xl font-medium text-head">{{ $attendance->student->student_number }} — {{ $attendance->student->name_en }}</h1>
        <p>{{ __('Attendance date') }}: {{ $attendance->date->format('d M Y') }} · {{ __($attendance->status) }}</p>
        <p>{{ __('Recorded placement') }}: {{ $attendance->classroom->academicYear->year }} / {{ $attendance->classroom->grade }} / {{ __($attendance->classroom->shift) }} / {{ $attendance->classroom->section }} · {{ __('Roll') }} {{ $attendance->roll }} @if($attendance->group) · {{ __($attendance->group) }} @endif</p>
        <p>{{ __('Recorded by') }}: {{ $attendance->recordedBy?->name ?? __('Unknown user') }} · {{ $attendance->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</p>
        <a class="text-primary underline" href="{{ route('attendance.index', ['classroom_id' => $attendance->classroom_id, 'date' => $attendance->date->toDateString()]) }}">{{ __('Back to daily attendance') }}</a>
    </x-card>
    @can('attendance.correct')
    <x-card class="mb-6 p-6">
        <h2 class="mb-4 font-medium text-head">{{ __('Correct attendance') }}</h2>
        <form method="POST" action="{{ route('attendance.update', $attendance) }}" class="grid items-end gap-4 sm:grid-cols-3">
            @csrf @method('PATCH')
            <input type="hidden" name="expected_version" value="{{ $attendance->version }}">
            <x-field label="New attendance status" name="status" type="select" required :options="['' => __('Select status')] + collect(\App\Models\Attendance::STATUSES)->reject(fn ($status) => $status === $attendance->status)->mapWithKeys(fn ($status) => [$status => __($status)])->all()" />
            <x-field label="Correction reason" name="reason" maxlength="1000" required />
            <x-button>{{ __('Save correction') }}</x-button>
        </form>
    </x-card>
    @endcan
    <x-card class="overflow-x-auto p-6">
        <h2 class="mb-4 font-medium text-head">{{ __('Attendance correction history') }}</h2>
        <table class="tbl w-full"><thead><tr><th>{{ __('Previous status') }}</th><th>{{ __('New status') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Corrected by') }}</th><th>{{ __('Recorded at') }}</th></tr></thead><tbody>
            @forelse($corrections as $correction)
            <tr><td>{{ __($correction->old_status) }}</td><td>{{ __($correction->new_status) }}</td><td>{{ $correction->reason }}</td><td>{{ $correction->correctedBy?->name ?? __('Unknown user') }}</td><td>{{ $correction->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td></tr>
            @empty<tr><td colspan="5">{{ __('No attendance corrections yet.') }}</td></tr>@endforelse
        </tbody></table>
        {{ $corrections->links() }}
    </x-card>
</x-app-layout>
