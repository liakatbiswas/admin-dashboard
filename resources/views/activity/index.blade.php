<x-app-layout title="Activity Log">
    @php
        $fmt = fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (is_array($v) ? json_encode($v) : $v ?? '—');
    @endphp

    <section class="rounded-lg bg-card shadow-card overflow-hidden">
        <form method="GET" class="border-b border-line p-4 flex flex-wrap gap-2 items-center">
            <input name="q" value="{{ request('q') }}" placeholder="বিবরণ খুঁজুন"
                class="w-[200px]! max-w-full w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">

            <select name="user"
                class="w-auto! w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">
                <option value="">সব User</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>

            <select name="log"
                class="w-auto! w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">
                <option value="">সব ধরন</option>
                @foreach ($logNames as $name)
                    <option value="{{ $name }}" @selected(request('log') === $name)>{{ $name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}"
                class="w-auto! w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">
            <input type="date" name="to" value="{{ request('to') }}"
                class="w-auto! w-full rounded-md border border-line bg-card px-3 py-2 text-sm text-head placeholder:text-mute focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary/45 disabled:bg-soft read-only:bg-soft [&.bad]:border-red-500 [&[type=file]]:px-2 [&[type=file]]:py-1.5">

            <button
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-soft text-head hover:bg-line">Filter</button>
            <a href="{{ route('activity.index') }}"
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 border-line! bg-transparent text-head hover:bg-soft">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table
                class="w-full border-collapse [&_th]:whitespace-nowrap [&_th]:bg-soft [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-medium [&_th]:text-head [&_td]:whitespace-nowrap [&_td]:border-t [&_td]:border-line [&_td]:px-4 [&_td]:py-3 [&_tbody_tr:hover]:bg-soft [&_tbody_tr:nth-child(even)]:bg-soft">
                <thead>
                    <tr>
                        <th>সময়</th>
                        <th>কে</th>
                        <th>কী করেছে</th>
                        <th>বিষয়</th>
                        <th>বিস্তারিত</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $a)
                        @php
                            $changes = $a->properties->get('attributes', []);
                            $old = $a->properties->get('old', []);
                            $ip = $a->properties->get('ip');
                        @endphp
                        <tr class="align-top">
                            <td class="text-mute">{{ $a->created_at->format('d M Y, h:i A') }}</td>
                            <td>{{ $a->causer?->name ?? ($a->causer_id ? 'Deleted user #' . $a->causer_id : 'System') }}
                            </td>
                            <td>
                                <span
                                    class="inline-flex rounded px-2 py-0.5 text-xs font-medium bg-tint text-primary mr-1">{{ $a->log_name }}</span>
                                {{ $a->description }}
                            </td>
                            <td>
                                @if ($a->subject_type)
                                    {{ class_basename($a->subject_type) }} #{{ $a->subject_id }}
                                    <span class="text-mute">{{ data_get($a->subject, 'name') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($changes || $ip)
                                    <details class="text-xs">
                                        <summary class="text-primary cursor-pointer">দেখুন</summary>
                                        @foreach ($changes as $key => $new)
                                            <div>{{ $key }}: <span
                                                    class="text-mute">{{ $fmt($old[$key] ?? null) }}</span> →
                                                {{ $fmt($new) }}</div>
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
                            <td colspan="5" class="text-mute text-center py-6">কোনো রেকর্ড নেই।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $activities->links() }}</div>
    </section>
</x-app-layout>
