<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Gate::before(fn ($user, $ability) => $user->hasRole('super-admin') ? true : null);

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
    }
}
