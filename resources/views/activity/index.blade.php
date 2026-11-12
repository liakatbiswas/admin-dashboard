<x-app-layout title="Activity log" :crumbs="['Activity log' => null]">

    <x-card class="mb-6 p-5">
        <form method="GET" action="{{ route('activity.index') }}"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <x-field label="Log" name="log" type="select" :value="request('log')" :options="$logOptions" />
            <x-field label="User (name, email or ID)" name="user" type="search" :value="request('user')" placeholder="{{ __('Search by name, email or ID') }}" />
            <x-field label="Description" name="q" type="search" :value="request('q')" placeholder="{{ __('Search description') }}" />
            <x-field label="From" name="from" type="date" :value="request('from')" />
            <x-field label="To" name="to" type="date" :value="request('to')" />
            <div class="flex items-end gap-2">
                <x-button class="flex-1">{{ __('Filter') }}</x-button>
                <a href="{{ route('activity.index') }}"
                    class="inline-flex items-center rounded-md bg-soft px-4 py-2 text-sm font-medium text-head hover:bg-line">{{ __('Reset') }}</a>
            </div>
        </form>
    </x-card>

    <x-card class="overflow-x-auto">
        <table
            class="w-full border-collapse [&_th]:whitespace-nowrap [&_th]:bg-soft [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-medium [&_th]:text-head [&_td]:whitespace-nowrap [&_td]:border-t [&_td]:border-line [&_td]:px-4 [&_td]:py-3 [&_tbody_tr:hover]:bg-soft">
            <thead>
                <tr>
                    <th>{{ __('When') }}</th>
                    <th>{{ __('Log') }}</th>
                    <th>{{ __('Event') }}</th>
                    <th>{{ __('By') }}</th>
                    <th>{{ __('Subject') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td class="text-mute" title="{{ $activity->created_at }}">
                            {{ $activity->created_at->locale(app()->getLocale())->diffForHumans() }}</td>
                        <td><span
                                class="rounded bg-tint px-2 py-0.5 text-xs font-medium text-primary">{{ $activity->log_name ?? 'default' }}</span>
                        </td>
                        <td class="whitespace-normal text-head">{{ $activity->description }}</td>
                        <td>{{ $activity->causer?->name ?? __('System') }}</td>
                        <td class="text-mute">
                            {{ $activity->subject_type ? class_basename($activity->subject_type) . ' #' . $activity->subject_id : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-mute">{{ __('No activity found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-6">{{ $activities->links() }}</div>
</x-app-layout>
