<x-app-layout title="Role Edit">
    <form method="POST" action="{{ route('roles.update', $role) }}" class="bg-white rounded-xl shadow p-6 max-w-2xl">
        @csrf
        @method('PUT')
        @include('roles._form')
        <button class="bg-indigo-600 text-white rounded-lg px-4 py-2 mt-2">Update Role</button>
        <a href="{{ route('roles.index') }}" class="ml-3 text-sm text-gray-600">Cancel</a>
    </form>
</x-app-layout>
