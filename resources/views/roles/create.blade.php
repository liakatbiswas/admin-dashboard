<x-app-layout title="নতুন Role">
<section class="card p-6 max-w-3xl">
    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        @include('roles._form')
        <div class="flex flex-wrap gap-3">
            <button class="btn btn-p">Create Role</button>
            <a href="{{ route('roles.index') }}" class="btn btn-s">Cancel</a>
        </div>
    </form>
</section>
</x-app-layout>
