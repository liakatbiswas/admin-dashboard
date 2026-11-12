<x-app-layout title="Registration on/off" :crumbs="['Settings' => route('settings.index'), 'Registration on/off' => null]">
    <div class="max-w-4xl">
        <x-card class="p-6">
            <h2 class="text-[15px] font-medium text-head">{{ __('Registration on/off') }}</h2>
            <p class="mb-5 mt-1 text-mute">{{ __('Control whether visitors can register new accounts.') }} </p>
            @include('settings._admission-toggle', [
                'admissionOn' => $admissionOn,
            ])
        </x-card>
    </div>
</x-app-layout>
