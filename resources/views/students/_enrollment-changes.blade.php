@foreach($student->enrollments as $enrollment)
    @canany(['students.transfers.manage', 'students.groups.manage'])
    <x-card class="mb-6 space-y-4 p-6">
        <h2 class="font-medium text-head">{{ __('Change academic placement') }} · {{ $enrollment->classroom->academicYear->year }} / {{ $enrollment->classroom->grade }} / {{ __($enrollment->classroom->shift) }} / {{ $enrollment->classroom->section }}</h2>
        <p class="text-mute">{{ __('Changes apply immediately. Use an effective date within this academic year, no later than today. Student ID stays the same.') }}</p>
        @can('students.transfers.manage')
            @can('students.rolls.manage')
            <form method="POST" action="{{ route('students.transfer', [$student, $enrollment]) }}" class="space-y-4 rounded-md border border-line p-4">
                @csrf @method('PATCH')
                <h3 class="font-medium text-head">{{ __('Change shift or section') }}</h3>
                <input type="hidden" name="expected_classroom_id" value="{{ $enrollment->classroom_id }}">
                <input type="hidden" name="expected_roll" value="{{ $enrollment->roll }}">
                <input type="hidden" name="expected_group" value="{{ $enrollment->group }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Destination classroom" name="classroom_id" :id="'destination-'.$enrollment->id" type="select" required :options="['' => __('Select classroom')] + $classrooms->filter(fn ($room) => $room->academic_year_id === $enrollment->academic_year_id && $room->grade === $enrollment->classroom->grade && $room->id !== $enrollment->classroom_id)->mapWithKeys(fn ($room) => [$room->id => __($room->shift).' / '.$room->section])->all()" />
                    <x-field label="New roll" name="roll" :id="'transfer-roll-'.$enrollment->id" type="number" min="1" max="9999" required />
                    <x-field label="Effective date" name="effective_date" :id="'transfer-date-'.$enrollment->id" type="date" :min="$enrollment->classroom->academicYear->year.'-01-01'" :max="min($enrollment->classroom->academicYear->year.'-12-31', now('Asia/Dhaka')->toDateString())" required />
                    <x-field label="Change reason" name="reason" :id="'transfer-reason-'.$enrollment->id" maxlength="1000" required />
                </div>
                @role('super-admin')
                <x-field label="Capacity override reason (only if full)" name="override_reason" :id="'transfer-override-'.$enrollment->id" maxlength="1000" />
                @endrole
                <p class="text-sm text-mute">{{ __('The current group is kept. To change it, use the separate group form.') }}</p>
                <x-button>{{ __('Transfer student') }}</x-button>
            </form>
            @else
            <p class="text-mute">{{ __('Class Roll permission is also required to transfer a student.') }}</p>
            @endcan
        @endcan
        @can('students.groups.manage')
            @if($enrollment->classroom->grade >= 9)
            <form method="POST" action="{{ route('students.group', [$student, $enrollment]) }}" class="space-y-4 rounded-md border border-line p-4">
                @csrf @method('PATCH')
                <h3 class="font-medium text-head">{{ __('Change group') }} · {{ __($enrollment->group ?? 'Not assigned') }}</h3>
                <input type="hidden" name="expected_classroom_id" value="{{ $enrollment->classroom_id }}">
                <input type="hidden" name="expected_roll" value="{{ $enrollment->roll }}">
                <input type="hidden" name="expected_group" value="{{ $enrollment->group }}">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="New group" name="group" :id="'group-'.$enrollment->id" type="select" required :options="['' => __('Select group')] + collect(\App\Models\Classroom::GROUPS)->reject(fn ($group) => $group === $enrollment->group)->mapWithKeys(fn ($group) => [$group => __($group)])->all()" />
                    <x-field label="Effective date" name="effective_date" :id="'group-date-'.$enrollment->id" type="date" :min="$enrollment->classroom->academicYear->year.'-01-01'" :max="min($enrollment->classroom->academicYear->year.'-12-31', now('Asia/Dhaka')->toDateString())" required />
                    <x-field label="Change reason" name="reason" :id="'group-reason-'.$enrollment->id" maxlength="1000" required />
                </div>
                <x-button>{{ __('Update group') }}</x-button>
            </form>
            @endif
        @endcan
    </x-card>
    @endcanany
@endforeach
<x-card class="mb-6 overflow-x-auto p-6">
    <h2 class="mb-4 font-medium text-head">{{ __('Placement change history') }}</h2>
    <table class="tbl w-full">
        <thead><tr><th>{{ __('Change') }}</th><th>{{ __('Previous placement') }}</th><th>{{ __('New placement') }}</th><th>{{ __('Effective date') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Changed by') }}</th><th>{{ __('Recorded at') }}</th></tr></thead>
        <tbody>
            @forelse($enrollmentChanges as $change)
            <tr>
                <td>{{ __($change->type === 'transfer' ? 'Shift / Section' : 'Group') }}</td>
                @foreach([$change->before, $change->after] as $placement)
                <td>{{ $placement['academic_year'] }} / {{ $placement['grade'] }} / {{ __($placement['shift']) }} / {{ $placement['section'] }}<br>{{ __('Roll') }}: {{ $placement['roll'] }} @if($placement['group']) · {{ __($placement['group']) }} @endif</td>
                @endforeach
                <td>{{ $change->effective_date->format('d M Y') }}</td>
                <td>{{ $change->reason }} @if($change->override_reason)<p class="mt-2 text-sm">{{ __('Capacity override') }}: {{ $change->override_reason }}</p>@endif</td>
                <td>{{ $change->changedBy?->name ?? __('Unknown user') }}</td>
                <td>{{ $change->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
            </tr>
            @empty<tr><td colspan="7">{{ __('No placement changes yet.') }}</td></tr>@endforelse
        </tbody>
    </table>
    {{ $enrollmentChanges->links() }}
</x-card>
