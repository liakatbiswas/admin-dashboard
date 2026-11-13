<x-guest-layout title="Registration Status">
    <h1 class="text-center text-xl font-medium text-head">{{ __('The registration period has ended.') }}</h1>
    <p class="my-6 text-center text-mute">{{ __('Existing users can still sign in. Contact the school office about an earlier application.') }}</p>
    <a href="{{ route('login') }}" class="block text-center text-primary">{{ __('Log in') }}</a>
    <a href="{{ route('admissions.selected') }}" class="mt-4 block text-center text-primary">{{ __('Selected students') }}</a>
</x-guest-layout>
