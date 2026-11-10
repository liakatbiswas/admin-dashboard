<x-guest-layout>
    <h4 class="hd font-medium text-center" style="font-size:20px">Two-factor authentication</h4>
    <p class="muted text-center mt-1 mb-6">Authenticator অ্যাপের কোড দিন, অথবা একটি recovery code ব্যবহার করুন।</p>

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf
        <x-field label="Authentication code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
        <x-field label="Recovery code" name="recovery_code" />
        <button class="btn btn-p w-full">Verify</button>
    </form>
</x-guest-layout>
