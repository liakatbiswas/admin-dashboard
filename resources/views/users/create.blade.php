<x-app-layout title="Create User">
    <form method="POST" action="{{ route('users.store') }}" class="bg-white rounded-xl shadow p-6 max-w-xl">
        @csrf
        @include('users._form', ['user' => null])
        <p class="text-sm text-gray-500 mb-4">ইমেইলে পাসওয়ার্ড সেট করার লিংক চলে যাবে।</p>
        <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">Create & Send Invite</button>
        <a href="{{ route('users.index') }}" class="ml-3 text-sm text-gray-600">Cancel</a>
    </form>
</x-app-layout>
