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
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->filled('log'), fn ($q) => $q->where('log_name', $request->log))
            ->when($request->filled('user'), fn ($q) => $q
                ->where('causer_type', User::class)
                ->where('causer_id', $request->user))
            ->when($request->filled('q'), fn ($q) => $q
                ->where('description', 'like', '%'.$request->q.'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('activity.index', [
            'activities' => $activities,
            'users' => User::withTrashed()->orderBy('name')->get(['id', 'name']),
            'logNames' => Activity::query()->distinct()->pluck('log_name')->filter()->values(),
        ]);
    }
}
