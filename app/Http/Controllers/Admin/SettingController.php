<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'registrationEnabled' => Setting::registrationEnabled(),
        ]);
    }

    public function toggleRegistration(): RedirectResponse
    {
        $enable = ! Setting::registrationEnabled();

        Setting::set('registration_enabled', $enable ? '1' : '0');

        activity()
            ->causedBy(auth()->user())
            ->log($enable ? 'Registration enabled' : 'Registration disabled');

        return back()->with('success', $enable ? 'Registration has been enabled.' : 'Registration has been disabled.');
    }
}
