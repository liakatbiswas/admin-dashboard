<x-app-layout title="নতুন User">
<section class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users._form', ['user' => null])
        <p class="muted text-sm mb-4">ইমেইলে পাসওয়ার্ড সেট করার লিংক চলে যাবে।</p>
        <div class="flex flex-wrap gap-3">
            <button class="btn btn-p">Create & Send Invite</button>
            <a href="{{ route('users.index') }}" class="btn btn-s">Cancel</a>
        </div>
    </form>
</section>
</x-app-layout>
