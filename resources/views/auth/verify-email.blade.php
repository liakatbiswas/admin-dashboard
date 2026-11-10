<x-guest-layout>
    <h4 class="text-xl text-head font-medium text-center">Verify your email</h4>
    <p class="text-mute text-center mt-2 mb-6">আপনার ইমেইলে পাঠানো লিংকে ক্লিক করে ইমেইল যাচাই করুন।</p>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
        @csrf
        <button
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8] w-full">Resend
            email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button class="text-mute text-sm underline">Log out</button>
    </form>
</x-guest-layout>
