<x-guest-layout>
    <h4 class="text-xl text-head font-medium text-center">Create an account</h4>
    <p class="text-mute text-center mt-1 mb-6">Fill in the form below to register.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <x-field label="Name" name="name" required autofocus />
        <x-field label="Email address" name="email" type="email" required />
        <x-field label="Password" name="password" type="password" required />
        <x-field label="Confirm password" name="password_confirmation" type="password" required />
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8] w-full mt-2">Register</button>
    </form>

    <p class="text-mute text-center text-sm mt-6">
        Already registered? <a href="{{ route('login') }}" class="text-primary font-medium">Log in</a>
    </p>
</x-guest-layout>
