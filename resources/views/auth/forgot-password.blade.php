<x-guest-layout>
    <h4 class="hd font-medium text-center" style="font-size:20px">Reset password</h4>
    <p class="muted text-center mt-1 mb-6">Enter your email and we will send you a reset link.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-field label="Email address" name="email" type="email" required autofocus />
        <button class="btn btn-p w-full">Send reset link</button>
    </form>

    <p class="muted text-center text-sm mt-6">
        <a href="{{ route('login') }}" class="font-medium" style="color:var(--pr)">Back to login</a>
    </p>
</x-guest-layout>
