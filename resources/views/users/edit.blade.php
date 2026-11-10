<x-app-layout title="User Edit">
<section class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users._form')
        <div class="flex flex-wrap gap-3">
            <button class="btn btn-p">Update</button>
            <a href="{{ route('users.index') }}" class="btn btn-s">Cancel</a>
        </div>
    </form>
</section>
</x-app-layout>
