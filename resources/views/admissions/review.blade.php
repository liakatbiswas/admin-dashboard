<x-app-layout title="Review application" :crumbs="['Admission applications' => route('applications.index'), 'Review' => null]">
    @if($errors->any())<div role="alert" class="mb-4 text-bad">{{ $errors->first() }}</div>@endif
    <x-card class="mb-6 p-6"><h1 class="text-xl font-medium">{{ $application->number }} — {{ $application->name_en }}</h1>
        <p>{{ $application->name_bn }} · {{ __('Class') }} {{ $application->grade }} · {{ __(ucfirst($application->status)) }}</p>
        <p class="mt-3">{{ __('Guardian') }}: {{ $application->guardian['name'] }} · {{ $application->guardian['phone'] }}</p>
        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('admissions.receipt', now()->addDays(7), ['application' => $application->id]) }}" class="mt-3 inline-block text-primary">{{ __('View or print application') }}</a>
        @if($application->photo_path)<img src="{{ route('applications.photo', $application) }}" alt="{{ __('Student photo') }}" class="mt-4 size-24 rounded-md object-cover">@endif
    </x-card>
    @can('applications.manage')
    @if($application->status !== 'admitted')
    <x-card class="mb-6 p-6"><h2 class="mb-4 font-medium">{{ __('Selection') }}</h2>
        <form method="POST" action="{{ route('applications.select', $application) }}" class="space-y-4">@csrf
            <x-field label="Status" name="status" type="select" :options="['selected' => __('Selected'), 'rejected' => __('Rejected')]" />
            <x-field label="Selected shift" name="selected_shift" type="select" :options="['Morning' => __('Morning'), 'Day' => __('Day')]" />
            <x-field label="Reason" name="reason" required /><x-button>{{ __('Save selection') }}</x-button>
        </form>
        @if($application->status === 'selected' && !$application->published_at)<form method="POST" action="{{ route('applications.publish', $application) }}" class="mt-4">@csrf<x-button>{{ __('Publish selection') }}</x-button></form>@endif
    </x-card>
    @if(!$application->published_at)
    <x-card class="mb-6 p-6"><h2 class="mb-4 font-medium">{{ __('Correct application') }}</h2>
        <form method="POST" action="{{ route('applications.update', $application) }}" enctype="multipart/form-data" class="space-y-4">@csrf @method('PUT')
            @include('admissions._form')<x-field label="Correction reason" name="reason" required /><x-button>{{ __('Save changes') }}</x-button>
        </form>
    </x-card>
    @endif @endif @endcan
    @can('applications.admit')
    @if($application->status === 'selected' && $application->published_at)
    <x-card class="p-6"><h2 class="mb-4 font-medium">{{ __('Confirm admission') }}</h2>
        <form method="POST" action="{{ route('applications.admit', $application) }}">@csrf @include('students._placement')<x-button>{{ __('Confirm admission') }}</x-button></form>
    </x-card>
    @endif @endcan
</x-app-layout>
