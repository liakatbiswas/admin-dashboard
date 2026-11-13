<x-app-layout title="Add existing student" :crumbs="['Students' => route('students.index'), 'Add existing student' => null]">
    <x-card class="max-w-4xl p-6"><p class="mb-4 text-mute">{{ __('Search the student list first. Use the actual admission year, class and serial from school records.') }}</p>
    <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf @include('admissions._form')
        <div class="grid gap-4 sm:grid-cols-3"><x-field label="Actual admission year" name="admission_year" type="number" min="1900" max="2200" required /><x-field label="Admission class" name="admission_grade" type="select" :options="array_combine(\App\Models\Classroom::GRADES, \App\Models\Classroom::GRADES)" required /><x-field label="Admission serial" name="admission_serial" type="number" min="1" max="99999" required /></div>
        @include('students._placement')<x-button>{{ __('Add existing student') }}</x-button>
    </form></x-card>
</x-app-layout>
