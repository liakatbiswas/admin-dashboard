<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (! $user->hasVerifiedEmail() || ($user->requiresAdministrativeProfile()
            && (! $user->profileIsComplete() || ! $user->contactsAreVerified())))
            && ! $request->routeIs('profile.edit', 'user-profile-information.update',
                'password.change.required', 'user-password.update', 'password.confirm',
                'password.confirm.store', 'password.confirmation', 'logout', 'locale.update')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => __('Complete your profile and contact verification before continuing.')], 403);
            }

            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}
