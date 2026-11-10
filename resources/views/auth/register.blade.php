<x-guest-layout>
    <h4 class="hd font-medium text-center" style="font-size:20px">Create an account</h4>
    <p class="muted text-center mt-1 mb-6">Fill in the form below to register.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <x-field label="Name" name="name" required autofocus />
        <x-field label="Email address" name="email" type="email" required />
        <x-field label="Password" name="password" type="password" required />
        <x-field label="Confirm password" name="password_confirmation" type="password" required />
        <button class="btn btn-p w-full mt-2">Register</button>
    </form>

    <p class="muted text-center text-sm mt-6">
        Already registered? <a href="{{ route('login') }}" class="font-medium" style="color:var(--pr)">Log in</a>
    </p>
</x-guest-layout>
