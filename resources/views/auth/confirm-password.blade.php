<x-guest-layout>
    <h4 class="hd font-medium text-center" style="font-size:20px">Confirm password</h4>
    <p class="muted text-center mt-1 mb-6">এই কাজটি করার আগে আপনার পাসওয়ার্ড আবার দিন।</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <x-field label="Password" name="password" type="password" required autofocus />
        <button class="btn btn-p w-full">Confirm</button>
    </form>
</x-guest-layout>
