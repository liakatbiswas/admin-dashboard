<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        return view('dashboard', [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'totalRoles' => Role::count(),
            'registrationEnabled' => Setting::registrationEnabled(),
        ]);
    }
}
