<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'log' => ['nullable', 'string', 'max:255'],
            'user' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_filter([
                'nullable',
                'date_format:Y-m-d',
                $request->filled('from') ? 'after_or_equal:from' : null,
            ]),
        ]);

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($filters['log'] ?? null, fn ($q, $log) => $q->where('log_name', $log))
            ->when($filters['user'] ?? null, function ($q, $user) {
                $term = trim($user);

                $q->where('causer_type', (new User)->getMorphClass())
                    ->whereIn('causer_id', User::withTrashed()
                        ->select('id')
                        ->where(function ($users) use ($term) {
                            if (ctype_digit($term)) {
                                $users->whereKey((int) $term);
                            } else {
                                $users->where('name', 'like', '%'.$term.'%')
                                    ->orWhere('email', 'like', '%'.$term.'%');
                            }
                        }));
            })
            ->when($filters['q'] ?? null, fn ($q, $term) => $q
                ->where('description', 'like', '%'.$term.'%'))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('activity.index', [
            'activities' => $activities,
            'logNames' => Activity::query()->distinct()->pluck('log_name')->filter()->values(),
        ]);
    }
}
