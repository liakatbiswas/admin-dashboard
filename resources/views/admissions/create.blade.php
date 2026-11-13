<x-guest-layout :wide="true" title="Admission application">
    <h1 class="mb-4 text-xl font-medium text-head">{{ __('Admission application') }}</h1>
    <p class="mb-6 text-mute">{{ __('Submitting an application does not create a login account. Keep your application number and receipt link.') }}</p>
    <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf @include('admissions._form')
        <x-button class="w-full">{{ __('Submit application') }}</x-button>
    </form>
    <a href="{{ route('admissions.selected') }}" class="mt-4 block text-primary">{{ __('Selected students') }}</a>
</x-guest-layout>
