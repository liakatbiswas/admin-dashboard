<?php

use App\Http\Controllers\Admin\AcademicController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ContactVerificationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::post('/language', LocaleController::class)->name('locale.update');

Route::get('/password/change-required', function (Request $request): View|RedirectResponse {
    return $request->user()->must_change_password
        ? view('auth.change-password-required')
        : redirect()->route('dashboard');
})->middleware('auth')->name('password.change.required');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [AdmissionController::class, 'create'])->name('register');
Route::post('/register', [AdmissionController::class, 'store'])->name('register.store');
Route::get('/applications/{application}/receipt', [AdmissionController::class, 'receipt'])->middleware('signed')->name('admissions.receipt');
Route::get('/selected-students', [AdmissionController::class, 'selected'])->name('admissions.selected');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/contact-verifications', [ContactVerificationController::class, 'index'])->middleware('can:users.verify')->name('contact-verifications.index');
    Route::post('/admin/contact-verifications/{user}', [ContactVerificationController::class, 'store'])->middleware(['can:users.verify', 'throttle:20,1'])->name('contact-verifications.store');
    Route::prefix('admin/academics')->controller(AcademicController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:academics.access')->name('academics.index');
        Route::post('/years', 'year')->middleware('can:academics.manage')->name('academics.years.store');
        Route::patch('/years/{year}/current', 'current')->middleware('can:academics.manage')->name('academics.years.current');
        Route::post('/classrooms', 'store')->middleware('can:academics.manage')->name('academics.classrooms.store');
        Route::put('/classrooms/{classroom}', 'update')->middleware('can:academics.manage')->name('academics.classrooms.update');
    });
    Route::prefix('admin/applications')->controller(App\Http\Controllers\Admin\AdmissionController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:applications.access')->name('applications.index');
        Route::get('/{application}', 'show')->middleware('can:applications.access')->name('applications.show');
        Route::get('/{application}/photo', 'photo')->middleware('can:applications.access')->name('applications.photo');
        Route::put('/{application}', 'update')->middleware('can:applications.manage')->name('applications.update');
        Route::post('/{application}/selection', 'select')->middleware('can:applications.manage')->name('applications.select');
        Route::post('/{application}/publish', 'publish')->middleware('can:applications.manage')->name('applications.publish');
        Route::post('/{application}/admit', 'admit')->middleware('can:applications.admit')->name('applications.admit');
    });
    Route::prefix('admin/students')->controller(StudentController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:students.access')->name('students.index');
        Route::get('/create', 'create')->middleware('can:students.import')->name('students.create');
        Route::post('/', 'store')->middleware('can:students.import')->name('students.store');
        Route::get('/{student}', 'show')->middleware('can:students.access')->name('students.show');
        Route::post('/{student}/enroll', 'enroll')->middleware('can:students.import')->name('students.enroll');
        Route::post('/{student}/guardian', 'guardian')->middleware('can:guardians.manage')->name('students.guardian');
        Route::post('/{student}/password', 'resetPassword')->middleware(['can:students.credentials', 'throttle:10,1'])->name('students.password');
    });
    Route::post('/admin/settings/admission-deadline', [SettingController::class, 'deadline'])->middleware('can:admission.manage')->name('settings.admission.deadline');
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
    Route::get('/admin/settings/two-factor', [ProfileController::class, 'twoFactor'])
        ->middleware('password.confirm')->name('settings.two-factor');

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
        Route::post('users/{user}/temporary-password', 'resetTemporaryPassword')
            ->middleware(['can:users.create', 'throttle:user-password-resets'])
            ->name('users.temporary-password');
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
