<x-guest-layout>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="email" value="{{ $request->email }}">
        <x-field label="New Password" name="password" type="password" required autofocus />
        <x-field label="Confirm Password" name="password_confirmation" type="password" required />
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2">Set password</button>
    </form>
</x-guest-layout>
