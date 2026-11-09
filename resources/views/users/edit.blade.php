<x-app-layout title="User Edit">

    <form method="POST" action="{{ route('users.update', $user) }}" class="bg-white rounded-xl shadow p-6 max-w-xl">
        @csrf
        @method('PUT')
        @include('users._form')
        <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">Update</button>
        <a href="{{ route('users.index') }}" class="ml-3 text-sm text-gray-600">Cancel</a>
    </form>
</x-app-layout>
