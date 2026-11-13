<x-app-layout title="Registration on/off" :crumbs="['Settings' => route('settings.index'), 'Registration on/off' => null]">
    <div class="max-w-4xl">
        <x-card class="p-6">
            <h2 class="text-[15px] font-medium text-head">{{ __('Registration on/off') }}</h2>
            <p class="mb-5 mt-1 text-mute">{{ __('Control whether visitors can submit admission applications.') }} </p>
            @include('settings._admission-toggle', [
                'admissionOn' => $admissionOn,
            ])
        </x-card>
    </div>
    <x-card class="mt-6 p-6"><form method="POST" action="{{ route('settings.admission.deadline') }}" class="space-y-4">@csrf
        <x-field label="Application deadline (Bangladesh time)" name="deadline" type="datetime-local" :value="$admissionSettings?->deadline?->timezone('Asia/Dhaka')->format('Y-m-d\TH:i')" />
        <p class="text-mute">{{ __('Leave empty for no deadline. The ON/OFF switch still applies.') }}</p><x-button>{{ __('Save deadline') }}</x-button>
    </form></x-card>
</x-app-layout>
