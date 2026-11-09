<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::middleware('can:settings.manage')->prefix('admin')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/registration', [SettingController::class, 'toggleRegistration'])
            ->name('settings.registration');
    });
});

Route::prefix('admin')->controller(UserController::class)->group(function () {
    Route::get('users', 'index')->middleware('can:users.view')->name('users.index');
    Route::get('users/create', 'create')->middleware('can:users.create')->name('users.create');
    Route::post('users', 'store')->middleware('can:users.create')->name('users.store');
    Route::get('users/{user}/edit', 'edit')->middleware('can:users.edit')->name('users.edit');
    Route::put('users/{user}', 'update')->middleware('can:users.edit')->name('users.update');
    Route::patch('users/{user}/toggle', 'toggle')->middleware('can:users.edit')->name('users.toggle');
    Route::post('users/{user}/invite', 'invite')->middleware('can:users.create')->name('users.invite');
    Route::delete('users/{user}', 'destroy')->middleware('can:users.delete')->name('users.destroy');
    Route::patch('users/{user}/restore', 'restore')->middleware('can:users.delete')
        ->withTrashed()->name('users.restore');
});

Route::middleware('can:roles.manage')->prefix('admin')->group(function () {
    Route::resource('roles', RoleController::class)->except('show');
});
