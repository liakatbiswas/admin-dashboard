<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteSettingRequest;
use App\Models\AdmissionOnOff;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'admissionOn' => AdmissionOnOff::isOn(),
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

        try {
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

            DB::transaction(function () use (&$siteSetting, &$oldPaths, $data, $uploads, $request): void {
                $siteSetting = SiteSetting::query()->lockForUpdate()->findOrFail(1);
                foreach ($uploads as $input => $column) {
                    if ($request->hasFile($input)) {
                        $oldPaths[$column] = $siteSetting->{$column};
                    }
                }
                $siteSetting->fill($data)->save();
                activity()->causedBy($request->user())
                    ->withProperties(['fields' => array_keys($data)])
                    ->log('Site settings updated');
            });
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

        return back()->with('success', 'Site settings have been updated.');
    }

    public function toggleAdmission(Request $request): RedirectResponse
    {
        $data = $request->validate(['is_on' => ['required', 'boolean']]);
        $enable = (bool) $data['is_on'];
        DB::transaction(function () use ($enable): void {
            AdmissionOnOff::set($enable);
            activity()->causedBy(auth()->user())
                ->log($enable ? 'Admissions enabled' : 'Admissions disabled');
        });

        return back()->with('success', $enable ? 'Admissions have been enabled.' : 'Admissions have been disabled.');
    }
}
