<x-guest-layout>
    <h4 class="text-xl text-head font-medium text-center">Two-factor authentication</h4>
    <p class="text-mute text-center mt-1 mb-6">Authenticator অ্যাপের কোড দিন, অথবা একটি recovery code ব্যবহার করুন।</p>

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf
        <x-field label="Authentication code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
        <x-field label="Recovery code" name="recovery_code" />
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8] w-full">Verify</button>
    </form>
</x-guest-layout>
