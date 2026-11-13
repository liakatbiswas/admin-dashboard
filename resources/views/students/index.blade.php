<x-app-layout title="Students" :crumbs="['Students' => null]">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4"><form method="GET" class="flex items-end gap-3"><x-field label="Student ID" name="q" :value="request('q')" /><x-button>{{ __('Search') }}</x-button></form>@can('students.import')<a href="{{ route('students.create') }}" class="text-primary">{{ __('Add existing student') }}</a>@endcan</div>
    <x-card class="overflow-x-auto"><table class="tbl w-full"><thead><tr><th>{{ __('Student ID') }}</th><th>{{ __('Name') }}</th><th>{{ __('Guardian') }}</th><th></th></tr></thead><tbody>
    @forelse($students as $student)<tr><td>{{ $student->student_number }}</td><td>{{ $student->name_en }}</td><td>{{ $student->guardianUser?->name ?? __('Not linked') }}</td><td><a href="{{ route('students.show', $student) }}" class="text-primary">{{ __('View') }}</a></td></tr>@empty<tr><td colspan="4">{{ __('No students yet.') }}</td></tr>@endforelse
    </tbody></table></x-card><div class="mt-4">{{ $students->links() }}</div>
</x-app-layout>
