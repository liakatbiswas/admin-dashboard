<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password
            && ! $request->routeIs('password.change.required', 'user-password.update', 'logout', 'locale.update')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => __('You must change your password before continuing.')], 403);
            }

            return redirect()->route('password.change.required');
        }

        return $next($request);
    }
}
