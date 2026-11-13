<x-app-layout title="Academic structure" :crumbs="['Academic structure' => null]">
    @if ($errors->any()) <div role="alert" class="mb-4 text-bad">{{ $errors->first() }}</div> @endif
    <x-card class="mb-6 p-6">
        <h2 class="font-medium text-head">{{ __('Academic structure') }}</h2>
        <p class="mt-2 text-mute">{{ __('Classes 6–10 · Morning and Day shifts · January–December academic year') }}</p>
        <p class="mt-2 text-mute">{{ __('Science, Humanities and Business Studies apply only to Classes 9–10.') }}</p>
    </x-card>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card class="p-6">
            <h2 class="mb-4 font-medium text-head">{{ __('Academic years') }}</h2>
            @can('academics.manage')
                <form action="{{ route('academics.years.store') }}" method="POST" class="mb-4 flex items-end gap-3">
                    @csrf <x-field label="Year" name="year" type="number" min="1900" max="2200" required />
                    <x-button>{{ __('Add year') }}</x-button>
                </form>
            @endcan
            @foreach ($years as $year)
                <div class="flex items-center justify-between gap-3 border-b border-line py-3">
                    <span>{{ $year->year }} · 01 Jan – 31 Dec @if($year->is_current) · {{ __('Current') }} @endif</span>
                    @can('academics.manage')
                        @unless($year->is_current)
                        <form method="POST" action="{{ route('academics.years.current', $year) }}">@csrf @method('PATCH')<x-button variant="s">{{ __('Set current') }}</x-button></form>
                        @endunless
                    @endcan
                </div>
            @endforeach
        </x-card>
        @can('academics.manage')
        <x-card class="p-6">
            <h2 class="mb-4 font-medium text-head">{{ __('Add classroom') }}</h2>
            <form action="{{ route('academics.classrooms.store') }}" method="POST" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <x-field label="Academic year" name="academic_year_id" type="select" :options="$years->pluck('year', 'id')->all()" required />
                <x-field label="Class" name="grade" type="select" :options="array_combine(\App\Models\Classroom::GRADES, \App\Models\Classroom::GRADES)" required />
                <x-field label="Shift" name="shift" type="select" :options="collect(\App\Models\Classroom::SHIFTS)->mapWithKeys(fn ($shift) => [$shift => __($shift)])->all()" required />
                <x-field label="Section" name="section" placeholder="A" maxlength="3" required />
                <x-field label="Capacity" name="capacity" type="number" min="1" max="5000" required />
                <div class="flex items-end"><x-button>{{ __('Add classroom') }}</x-button></div>
            </form>
        </x-card>
        @endcan
    </div>
    <form method="GET" class="my-6 flex items-end gap-3">
        <x-field label="Academic year" name="year" id="year-filter" type="select" :value="request('year')" :options="['' => __('All years')] + $years->pluck('year', 'id')->all()" />
        <x-button variant="s">{{ __('Filter') }}</x-button>
    </form>
    <x-card class="overflow-x-auto">
        <table class="tbl w-full"><thead><tr><th>{{ __('Academic year') }}</th><th>{{ __('Class') }}</th><th>{{ __('Shift') }}</th><th>{{ __('Section') }}</th><th>{{ __('Enrolled') }}</th><th>{{ __('Capacity and status') }}</th></tr></thead><tbody>
        @forelse ($classrooms as $room)
            <tr><td>{{ $room->academicYear->year }}</td><td>{{ $room->grade }}</td><td>{{ __($room->shift) }}</td><td>{{ $room->section }}</td><td>{{ $room->enrollments_count }}</td><td>
                @can('academics.manage')
                <form method="POST" action="{{ route('academics.classrooms.update', $room) }}" class="flex flex-wrap items-center gap-2">
                    @csrf @method('PUT')
                    <input aria-label="{{ __('Capacity') }}" type="number" name="capacity" min="1" max="5000" value="{{ $room->capacity }}" required class="w-24 rounded-md border border-line bg-card p-2">
                    <select aria-label="{{ __('Status') }}" name="is_active" class="rounded-md border border-line bg-card p-2"><option value="1" @selected($room->is_active)>{{ __('Active') }}</option><option value="0" @selected(!$room->is_active)>{{ __('Inactive') }}</option></select>
                    <x-button size="sm">{{ __('Save') }}</x-button>
                </form>
                @else {{ $room->capacity }} · {{ $room->is_active ? __('Active') : __('Inactive') }} @endcan
            </td></tr>
        @empty <tr><td colspan="6">{{ __('No classrooms yet. Add an academic year and classroom first.') }}</td></tr> @endforelse
        </tbody></table>
    </x-card>
    <div class="mt-4">{{ $classrooms->links() }}</div>
</x-app-layout>
