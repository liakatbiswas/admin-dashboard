<x-app-layout title="Activity Log">
    @php
        $fmt = fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (is_array($v) ? json_encode($v) : $v ?? '—');
    @endphp

    <section class="card overflow-hidden">
        <form method="GET" class="p-4 flex flex-wrap gap-2 items-center" style="border-bottom:1px solid var(--bd)">
            <input name="q" value="{{ request('q') }}" placeholder="বিবরণ খুঁজুন" class="fc" style="width:200px">

            <select name="user" class="fc" style="width:auto">
                <option value="">সব User</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>

            <select name="log" class="fc" style="width:auto">
                <option value="">সব ধরন</option>
                @foreach ($logNames as $name)
                    <option value="{{ $name }}" @selected(request('log') === $name)>{{ $name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}" class="fc" style="width:auto">
            <input type="date" name="to" value="{{ request('to') }}" class="fc" style="width:auto">

            <button class="btn btn-s">Filter</button>
            <a href="{{ route('activity.index') }}" class="btn btn-o">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="tbl st">
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
                            <td class="muted">{{ $a->created_at->format('d M Y, h:i A') }}</td>
                            <td>{{ $a->causer?->name ?? ($a->causer_id ? 'Deleted user #' . $a->causer_id : 'System') }}
                            </td>
                            <td>
                                <span class="bd bd-b mr-1">{{ $a->log_name }}</span>
                                {{ $a->description }}
                            </td>
                            <td>
                                @if ($a->subject_type)
                                    {{ class_basename($a->subject_type) }} #{{ $a->subject_id }}
                                    <span class="muted">{{ data_get($a->subject, 'name') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="white-space:normal">
                                @if ($changes || $ip)
                                    <details class="text-xs">
                                        <summary class="cursor-pointer" style="color:var(--pr)">দেখুন</summary>
                                        @foreach ($changes as $key => $new)
                                            <div>{{ $key }}: <span
                                                    class="muted">{{ $fmt($old[$key] ?? null) }}</span> →
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
                            <td colspan="5" class="muted text-center py-6">কোনো রেকর্ড নেই।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $activities->links() }}</div>
    </section>
</x-app-layout>
