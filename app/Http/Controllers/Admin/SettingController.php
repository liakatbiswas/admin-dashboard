<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteSettingRequest;
use App\Models\Setting;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'registrationEnabled' => Setting::registrationEnabled(),
            'siteSetting' => SiteSetting::current(),
        ]);
    }

    public function updateSite(SiteSettingRequest $request): RedirectResponse
    {
        $siteSetting = SiteSetting::current();
        $data = $request->validated();
        $uploads = [
            'favicon' => 'favicon_path',
            'light_logo' => 'light_logo_path',
            'dark_logo' => 'dark_logo_path',
        ];
        $oldPaths = [];
        $newPaths = [];

        foreach ($uploads as $input => $column) {
            unset($data[$input]);

            if (! $request->hasFile($input)) {
                continue;
            }

            $path = $request->file($input)->store('site-settings', 'public');

            if (! $path) {
                foreach ($newPaths as $newPath) {
                    Storage::disk('public')->delete($newPath);
                }

                return back()
                    ->withErrors([$input => 'The file could not be uploaded.'])
                    ->withInput();
            }

            $oldPaths[$column] = $siteSetting->{$column};
            $newPaths[] = $path;
            $data[$column] = $path;
        }

        $data['updated_by'] = $request->user()->id;

        try {
            $siteSetting->fill($data)->save();
        } catch (Throwable $exception) {
            foreach ($newPaths as $newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        SiteSetting::clearCache();

        foreach ($oldPaths as $column => $oldPath) {
            if ($oldPath && $oldPath !== $siteSetting->{$column}) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        activity()
            ->causedBy($request->user())
            ->withProperties(['fields' => array_keys($data)])
            ->log('Site settings updated');

        return back()->with('success', 'Site settings have been updated.');
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
