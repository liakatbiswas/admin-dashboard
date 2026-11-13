<x-app-layout title="Account details" :crumbs="['Students' => route('students.index'), 'Account details' => null]">
    <x-card class="max-w-3xl space-y-4 p-6"><h1 class="text-xl font-medium">{{ __('Account details') }}</h1>
        <p>{{ $student->student_number }} · {{ $student->name_en }}</p><p>{{ __('Account') }}: {{ $user->name }} · {{ $user->student_id ?? $user->verified_phone ?? $user->email }}</p>
        @if($password)<p>{{ __('This password is shown once. Give it to the user privately before leaving this page.') }}</p><p class="select-all break-all rounded-md bg-soft p-4 font-mono text-lg">{{ $password }}</p><p>{{ __('Expires at') }}: {{ $user->temporary_password_expires_at->timezone('Asia/Dhaka')->format('d M Y H:i') }} ({{ __('Bangladesh time') }})</p>
        @else<p>{{ __('The existing account was linked. Its password was not changed.') }}</p>@endif
        <a href="{{ route('students.show', $student) }}" class="text-primary">{{ __('View student record') }}</a>
    </x-card>
</x-app-layout>
