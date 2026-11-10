<x-app-layout title="User Edit">
    <section class="rounded-lg bg-card shadow-card p-6 max-w-2xl">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users._form')
            <div class="flex flex-wrap gap-3">
                <button
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-primary text-white hover:bg-[#2862d8]">Update</button>
                <a href="{{ route('users.index') }}"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-soft text-head hover:bg-line">Cancel</a>
            </div>
        </form>
    </section>
</x-app-layout>
