<x-app-layout title="Student record" :crumbs="['Students' => route('students.index'), 'Student record' => null]">
    @if($errors->any())<div role="alert" class="mb-4 text-bad">{{ $errors->first() }}</div>@endif
    <x-card class="mb-6 p-6"><h1 class="text-xl font-medium">{{ $student->student_number }} — {{ $student->name_en }}</h1><p>{{ $student->name_bn }}</p>
        <p>{{ __('Guardian') }}: {{ $student->guardian['name'] }} · {{ $student->guardian['phone'] }}</p>
        <p>{{ __('Linked account') }}: {{ $student->guardianUser?->name ?? __('Not linked') }}</p>
        @foreach($student->enrollments as $enrollment)<p class="mt-3">{{ $enrollment->classroom->academicYear->year }} / {{ $enrollment->classroom->grade }} / {{ __($enrollment->classroom->shift) }} / {{ $enrollment->classroom->section }} · {{ __('Roll') }} {{ $enrollment->roll }} @if($enrollment->group) · {{ __($enrollment->group) }} @endif</p>@endforeach
    </x-card>
    @can('students.rolls.manage')
    <x-card class="mb-6 space-y-4 p-6">
        <h2 class="font-medium text-head">{{ __('Change class roll') }}</h2>
        <p class="text-mute">{{ __('Rolls are separate for each academic year, class, shift and section. Student ID remains unchanged.') }}</p>
        @foreach($student->enrollments as $enrollment)
        <form method="POST" action="{{ route('students.roll.update', [$student, $enrollment]) }}" class="rounded-md border border-line p-4">
            @csrf @method('PATCH')
            <p class="mb-3 font-medium">{{ $enrollment->classroom->academicYear->year }} / {{ $enrollment->classroom->grade }} / {{ __($enrollment->classroom->shift) }} / {{ $enrollment->classroom->section }} · {{ __('Current roll') }}: {{ $enrollment->roll }}</p>
            <input type="hidden" name="expected_roll" value="{{ $enrollment->roll }}">
            <div class="grid items-end gap-4 sm:grid-cols-3">
                <x-field label="New roll" name="roll" :id="'roll-'.$enrollment->id" type="number" min="1" max="9999" required />
                <x-field label="Change reason" name="reason" :id="'roll-reason-'.$enrollment->id" required />
                <x-button>{{ __('Update roll') }}</x-button>
            </div>
        </form>
        @endforeach
    </x-card>
    @endcan
    <x-card class="mb-6 overflow-x-auto p-6">
        <h2 class="mb-4 font-medium text-head">{{ __('Roll change history') }}</h2>
        <table class="tbl w-full"><thead><tr><th>{{ __('Academic year') }}</th><th>{{ __('Class') }} / {{ __('Shift') }} / {{ __('Section') }}</th><th>{{ __('Old roll') }}</th><th>{{ __('New roll') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Changed by') }}</th><th>{{ __('Date') }}</th></tr></thead><tbody>
        @forelse($rollChanges as $change)
        <tr><td>{{ $change->properties['academic_year'] }}</td><td>{{ $change->properties['grade'] }} / {{ __($change->properties['shift']) }} / {{ $change->properties['section'] }}</td><td>{{ $change->properties['old_roll'] }}</td><td>{{ $change->properties['new_roll'] }}</td><td>{{ $change->properties['reason'] }}</td><td>{{ $change->causer?->name ?? __('Unknown user') }}</td><td>{{ $change->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td></tr>
        @empty<tr><td colspan="7">{{ __('No roll changes yet.') }}</td></tr>@endforelse
        </tbody></table>{{ $rollChanges->links() }}
    </x-card>
    @can('guardians.manage')<x-card class="mb-6 p-6"><h2 class="mb-4 font-medium">{{ __('Verify and link guardian') }}</h2>
        <p class="mb-4 text-mute">{{ __('Leave blank to match a verified mobile or create a new guardian account. Adding Guardian role to an existing account requires a Super Admin.') }}</p>
        <form method="POST" action="{{ route('students.guardian', $student) }}" class="space-y-4">@csrf
            <x-field label="Existing account email or mobile (optional)" name="account_lookup" /><x-field label="Verification reason" name="reason" required />
            <label class="flex items-center gap-2"><input type="checkbox" name="verified" value="1" required>{{ __('I verified the guardian identity and mobile number.') }}</label><x-button>{{ __('Verify and link guardian') }}</x-button>
        </form></x-card>@endcan
    @can('students.import')<x-card class="mb-6 p-6"><h2 class="mb-4 font-medium">{{ __('Enroll existing record') }}</h2>
        <form method="POST" action="{{ route('students.enroll', $student) }}">@csrf @include('students._placement')<x-button>{{ __('Enroll existing record') }}</x-button></form>
    </x-card>@endcan
    @can('students.credentials')<x-card class="p-6"><h2 class="mb-4 font-medium">{{ __('Reset temporary password') }}</h2>
        <form method="POST" action="{{ route('students.password', $student) }}" class="space-y-4">@csrf
            <x-field label="Account" name="account" type="select" :options="['student' => __('Student'), 'guardian' => __('Guardian')]" /><x-field label="Identity verification reason" name="reason" required /><x-button>{{ __('Reset temporary password') }}</x-button>
        </form>
    </x-card>@endcan
</x-app-layout>
