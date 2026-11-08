<x-guest-layout>
    <p class="text-sm mb-4">আপনার ইমেইলে পাঠানো লিংকে ক্লিক করে ইমেইল যাচাই করুন।</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
        @csrf
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2">Resend email</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="text-sm text-gray-600 underline">Logout</button>
    </form>
</x-guest-layout>
