<x-guest-layout>
    <h4 class="hd font-medium text-center" style="font-size:20px">Set password</h4>
    <p class="muted text-center mt-1 mb-6">Choose a new password for your account.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="email" value="{{ $request->email }}">
        <x-field label="New password" name="password" type="password" required autofocus />
        <x-field label="Confirm password" name="password_confirmation" type="password" required />
        <button class="btn btn-p w-full">Save password</button>
    </form>
</x-guest-layout>
