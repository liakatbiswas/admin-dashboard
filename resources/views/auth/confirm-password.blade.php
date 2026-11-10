<x-guest-layout>
    <h4 class="text-xl text-head font-medium text-center">Confirm password</h4>
    <p class="text-mute text-center mt-1 mb-6">এই কাজটি করার আগে আপনার পাসওয়ার্ড আবার দিন।</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <x-field label="Password" name="password" type="password" required autofocus />
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8] w-full">Confirm</button>
    </form>
</x-guest-layout>
