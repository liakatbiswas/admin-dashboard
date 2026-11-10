<x-guest-layout>
    <h4 class="text-xl text-head font-medium text-center">Reset password</h4>
    <p class="text-mute text-center mt-1 mb-6">Enter your email and we will send you a reset link.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-field label="Email address" name="email" type="email" required autofocus />
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8] w-full">Send
            reset link</button>
    </form>

    <p class="text-mute text-center text-sm mt-6">
        <a href="{{ route('login') }}" class="text-primary font-medium">Back to login</a>
    </p>
</x-guest-layout>
