<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        Gate::before(function ($user, $ability, $arguments = []) {
            $target = $arguments[0] ?? null;

            // অন্য super-admin-এর ওপর manage চেক policy-র হাতে ছেড়ে দিন
            if ($ability === 'manage' && $target instanceof User && $target->hasRole('super-admin') && ! $target->is($user)) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });

        Event::listen(Login::class, function (Login $e) {
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

        RateLimiter::for('user-invitations', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->user()->id),
                Limit::perHour(10)->by($request->user()->id),
            ];
        });
    }
}
