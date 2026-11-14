<x-app-layout title="Attendance" :crumbs="['Attendance' => null]">
    @if($errors->any())<div role="alert" class="mb-4 text-bad">{{ $errors->first() }}</div>@endif
    <x-card class="mb-6 space-y-4 p-6">
        <h1 class="text-xl font-medium text-head">{{ __('Daily attendance') }}</h1>
        <p class="text-mute">{{ __('Record each student once per day. Blank statuses remain unrecorded, not absent.') }}</p>
        <form method="GET" action="{{ route('attendance.index') }}" class="grid items-end gap-4 sm:grid-cols-3">
            <x-field label="Classroom" name="classroom_id" type="select" :value="$classroom?->id" required :options="['' => __('Select classroom')] + $classrooms->mapWithKeys(fn ($room) => [$room->id => $room->academicYear->year.' / '.$room->grade.' / '.__($room->shift).' / '.$room->section.($room->is_active ? '' : ' ('.__('Inactive').')')])->all()" />
            <x-field label="Attendance date" name="date" type="date" :value="$date" :max="now('Asia/Dhaka')->toDateString()" required />
            <x-button>{{ __('Load attendance') }}</x-button>
        </form>
    </x-card>
    @if($classroom)
    @php
        $canRecord = auth()->user()->can('attendance.record') && $classroom->is_active;
        $hasUnrecorded = $students->getCollection()->contains(fn ($row) => $row['attendance'] === null);
    @endphp
    <x-card class="overflow-x-auto p-6">
        <h2 class="mb-4 font-medium text-head">{{ $classroom->academicYear->year }} / {{ $classroom->grade }} / {{ __($classroom->shift) }} / {{ $classroom->section }} · {{ $date }}</h2>
        @unless($classroom->is_active)<p class="mb-4 text-mute">{{ __('This classroom is inactive. Existing attendance can still be viewed and corrected with permission.') }}</p>@endunless
        <form method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
            <input type="hidden" name="date" value="{{ $date }}">
            <table class="tbl w-full">
                <thead><tr><th>{{ __('Roll') }}</th><th>{{ __('Student') }}</th><th>{{ __('Group') }}</th><th>{{ __('Attendance status') }}</th></tr></thead>
                <tbody>
                    @forelse($students as $index => $row)
                    <tr>
                        <td>{{ $row['attendance']?->roll ?? $row['placement']['roll'] }}</td>
                        <td>{{ $row['enrollment']->student->name_en }}<br><span class="text-sm text-mute">{{ $row['enrollment']->student->student_number }}</span></td>
                        <td>{{ __($row['attendance'] ? ($row['attendance']->group ?? 'Not applicable') : ($row['placement']['group'] ?? 'Not applicable')) }}</td>
                        <td>
                            @if($row['attendance'])
                                <a class="text-primary underline" href="{{ route('attendance.show', $row['attendance']) }}">{{ __($row['attendance']->status) }} · {{ __('View record') }}</a>
                                @if($row['attendance']->classroom_id !== $classroom->id)<p class="text-sm text-mute">{{ __('Already recorded in another classroom on this date.') }}</p>@endif
                            @elseif($canRecord)
                                <input type="hidden" name="entries[{{ $index }}][enrollment_id]" value="{{ $row['enrollment']->id }}">
                                <select aria-label="{{ __('Attendance status') }} — {{ $row['enrollment']->student->student_number }}" name="entries[{{ $index }}][status]" class="rounded-md border border-line bg-card px-3 py-2 text-head">
                                    <option value="">{{ __('Not recorded') }}</option>
                                    @foreach(\App\Models\Attendance::STATUSES as $status)<option value="{{ $status }}">{{ __($status) }}</option>@endforeach
                                </select>
                            @else {{ __('Not recorded') }} @endif
                        </td>
                    </tr>
                    @empty<tr><td colspan="4">{{ __('No students in this classroom for the selected date.') }}</td></tr>@endforelse
                </tbody>
            </table>
            @if($canRecord && $hasUnrecorded)<div class="mt-4"><x-button>{{ __('Save selected attendance') }}</x-button></div>@endif
        </form>
        <div class="mt-4">{{ $students->links() }}</div>
    </x-card>
    @endif
</x-app-layout>
