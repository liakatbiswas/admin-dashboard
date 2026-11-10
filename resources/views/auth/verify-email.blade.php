<x-guest-layout>
    <h4 class="hd font-medium text-center" style="font-size:20px">Verify your email</h4>
    <p class="muted text-center mt-2 mb-6">আপনার ইমেইলে পাঠানো লিংকে ক্লিক করে ইমেইল যাচাই করুন।</p>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
        @csrf
        <button class="btn btn-p w-full">Resend email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button class="muted text-sm underline">Log out</button>
    </form>
</x-guest-layout>
