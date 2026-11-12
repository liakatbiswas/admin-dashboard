<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('settings.access', fn (User $user) => $user->canAny([
            'settings.site.manage',
            'admission.manage',
        ]));

        View::composer(['layouts.app', 'layouts.guest', 'components.logo', 'welcome'], function ($view) {
            $view->with('siteSettings', SiteSetting::current());
        });

        Gate::before(function ($user, $ability, $arguments = []) {
            $target = $arguments[0] ?? null;

            // Let the policy decide when managing another super admin.
            if ($ability === 'manage' && $target instanceof User && $target->hasRole('super-admin') && ! $target->is($user)) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });

        Event::listen(Login::class, function (Login $e) {
            if ($e->guard === 'web' && request()->hasSession()) {
                request()->session()->put('auth.version', (int) $e->user->auth_version);
            }
            activity('auth')->causedBy($e->user)
                ->withProperties(['ip' => request()->ip()])
                ->log('Logged in');
        });

        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                activity('auth')->causedBy($e->user)
                    ->withProperties(['ip' => request()->ip()])
                    ->log('Logged out');
            }
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            $event->user->tokens()->delete();
            $event->user->forceFill([
                'invited_at' => null,
                'email_verified_at' => $event->user->email_verified_at ?? now(),
            ])->save();
            activity('security')->causedBy($event->user)->log('Password reset');
        });

        Event::listen(TwoFactorAuthenticationEnabled::class, fn ($event) => activity('security')->causedBy($event->user)->log('Two-factor authentication enabled'));
        Event::listen(TwoFactorAuthenticationConfirmed::class, fn ($event) => activity('security')->causedBy($event->user)->log('Two-factor authentication confirmed'));
        Event::listen(TwoFactorAuthenticationDisabled::class, fn ($event) => activity('security')->causedBy($event->user)->log('Two-factor authentication disabled'));
        Event::listen(RecoveryCodesGenerated::class, fn ($event) => activity('security')->causedBy($event->user)->log('Two-factor recovery codes regenerated'));

        RateLimiter::for('user-creations', function (Request $request) {
            // Limit each administrator to ten user creations per minute.
            return Limit::perMinute(10)->by($request->user()->id);
        });
        RateLimiter::for('registration', fn (Request $request) => [
            Limit::perMinute(5)->by('registration:'.$request->ip()),
            Limit::perHour(20)->by('registration:'.$request->ip()),
        ]);
        RateLimiter::for('user-invitations', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->user()->id),
                Limit::perHour(10)->by($request->user()->id),
            ];
        });
    }
}
