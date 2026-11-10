<x-guest-layout>
    <h4 class="text-xl text-head font-medium text-center">Set password</h4>
    <p class="text-mute text-center mt-1 mb-6">Choose a new password for your account.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="email" value="{{ $request->email }}">
        <x-field label="New password" name="password" type="password" required autofocus />
        <x-field label="Confirm password" name="password_confirmation" type="password" required />
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8] w-full">Save
            password</button>
    </form>
</x-guest-layout>
