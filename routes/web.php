<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/language', LocaleController::class)->name('locale.update');

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->middleware('password.confirm')->name('profile.edit');

    Route::get('/admin/settings', [SettingController::class, 'index'])
        ->middleware('can:settings.access')
        ->name('settings.index');
    Route::get('/admin/settings/registration', [SettingController::class, 'registration'])
        ->middleware('can:admission.manage')->name('settings.registration');
    Route::get('/admin/settings/site', [SettingController::class, 'site'])
        ->middleware('can:settings.site.manage')->name('settings.site');
    Route::view('/admin/settings/password', 'settings.password')->name('settings.password');

    Route::put('/admin/settings/site', [SettingController::class, 'updateSite'])
        ->middleware('can:settings.site.manage')
        ->name('settings.site.update');
    Route::post('/admin/settings/admission', [SettingController::class, 'toggleAdmission'])
        ->middleware('can:admission.manage')
        ->name('settings.admission.toggle');

    Route::prefix('admin')->controller(UserController::class)->group(function () {
        Route::get('users', 'index')->middleware('can:users.view')->name('users.index');
        Route::get('users/create', 'create')->middleware('can:users.create')->name('users.create');
        Route::post('users', 'store')
            ->middleware(['can:users.create', 'throttle:user-creations'])
            ->name('users.store');
        Route::get('users/{user}/edit', 'edit')->middleware('can:users.edit')->name('users.edit');
        Route::put('users/{user}', 'update')->middleware('can:users.edit')->name('users.update');
        Route::patch('users/{user}/toggle', 'toggle')->middleware('can:users.edit')->name('users.toggle');
        Route::post('users/{user}/invite', 'invite')
            ->middleware(['can:users.create', 'throttle:user-invitations'])
            ->name('users.invite');
        Route::delete('users/{user}', 'destroy')->middleware('can:users.delete')->name('users.destroy');
        Route::patch('users/{user}/restore', 'restore')
            ->middleware('can:users.delete')
            ->withTrashed()
            ->name('users.restore');
        Route::delete('users/{user}/force-delete', 'forceDelete')
            ->middleware('can:users.delete')
            ->withTrashed()
            ->name('users.force-delete');
    });

    Route::middleware('can:roles.manage')->prefix('admin')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
    });

    Route::get('admin/activity', [ActivityLogController::class, 'index'])
        ->middleware('can:activity.view')
        ->name('activity.index');
});
