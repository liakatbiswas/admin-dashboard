<x-app-layout title="Activity Log">

    @php
        $fmt = fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (is_array($v) ? json_encode($v) : $v ?? '—');
    @endphp

    <div class="bg-white rounded-xl shadow">
        <form method="GET" class="p-4 flex flex-wrap gap-2 items-end">
            <input name="q" value="{{ request('q') }}" placeholder="বিবরণ খুঁজুন"
                class="border rounded-lg px-3 py-2 text-sm">

            <select name="user" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">সব User</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>

            <select name="log" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">সব ধরন</option>
                @foreach ($logNames as $name)
                    <option value="{{ $name }}" @selected(request('log') === $name)>{{ $name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}"
                class="border rounded-lg px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ request('to') }}"
                class="border rounded-lg px-3 py-2 text-sm">

            <button class="bg-gray-800 text-white rounded-lg px-4 py-2 text-sm">Filter</button>
            <a href="{{ route('activity.index') }}" class="text-sm text-gray-600 py-2">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th class="p-3">সময়</th>
                        <th class="p-3">কে</th>
                        <th class="p-3">কী করেছে</th>
                        <th class="p-3">বিষয়</th>
                        <th class="p-3">বিস্তারিত</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($activities as $a)
                        @php
                            $changes = $a->properties->get('attributes', []);
                            $old = $a->properties->get('old', []);
                            $ip = $a->properties->get('ip');
                        @endphp
                        <tr class="align-top">
                            <td class="p-3 whitespace-nowrap text-gray-500">{{ $a->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="p-3">
                                {{ $a->causer?->name ?? ($a->causer_id ? 'Deleted user #' . $a->causer_id : 'System') }}
                            </td>
                            <td class="p-3">
                                <span
                                    class="inline-block bg-gray-100 rounded px-2 py-0.5 text-xs mr-1">{{ $a->log_name }}</span>
                                {{ $a->description }}
                            </td>
                            <td class="p-3">
                                @if ($a->subject_type)
                                    {{ class_basename($a->subject_type) }} #{{ $a->subject_id }}
                                    <span class="text-gray-500">{{ data_get($a->subject, 'name') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($changes || $ip)
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-indigo-600">দেখুন</summary>
                                        @foreach ($changes as $key => $new)
                                            <div>{{ $key }}:
                                                <span class="text-gray-500">{{ $fmt($old[$key] ?? null) }}</span> →
                                                {{ $fmt($new) }}
                                            </div>
                                        @endforeach
                                        @if ($ip)
                                            <div>IP: {{ $ip }}</div>
                                        @endif
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">কোনো রেকর্ড নেই।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $activities->links() }}</div>
    </div>
</x-app-layout>
