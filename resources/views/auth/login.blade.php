<x-guest-layout>
    <h4 class="text-xl text-head font-medium text-center">Welcome back</h4>
    <p class="text-mute text-center mt-1 mb-6">Enter your email address and password to access the admin panel.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <x-field label="Email address" name="email" type="email" required autofocus />
        <x-field label="Password" name="password" type="password" required />
        <label class="flex items-center gap-2 text-sm mb-5">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8] w-full">Log
            in</button>
    </form>

    <div class="flex justify-between text-sm mt-6">
        <a href="{{ route('password.request') }}" class="text-primary font-medium">Forgot password?</a>
        <a href="{{ route('register') }}" class="text-primary font-medium">Register</a>
    </div>
</x-guest-layout>
