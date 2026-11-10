<x-app-layout title="নতুন User">
    <section class="rounded-lg bg-card shadow-card p-6 max-w-2xl">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users._form', ['user' => null])
            <p class="text-mute text-sm mb-4">ইমেইলে পাসওয়ার্ড সেট করার লিংক চলে যাবে।</p>
            <div class="flex flex-wrap gap-3">
                <button
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">Create
                    & Send Invite</button>
                <a href="{{ route('users.index') }}"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-soft text-head hover:bg-line">Cancel</a>
            </div>
        </form>
    </section>
</x-app-layout>
