<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $staleSession = $user && $request->hasSession()
            && $request->session()->has(Auth::guard('web')->getName())
            && $request->session()->get('auth.version') !== (int) $user->auth_version;

        if ($user && (! $user->is_active || $staleSession || $user->temporaryPasswordHasExpired())) {
            $message = $user->is_active ? __('Your session has expired. Please sign in again.') : __('Your account has been deactivated.');
            if ($user->is_active && $user->temporaryPasswordHasExpired()) {
                $message = __('Your temporary password has expired. Contact an administrator for a new password.');
            }
            if ($request->hasSession()) {
                if (Auth::guard('web')->id() === $user->getAuthIdentifier()) {
                    Auth::guard('web')->logout();
                }

                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $currentToken = $staleSession ? null : $user->currentAccessToken();
            if ($currentToken && method_exists($currentToken, 'delete')) {
                $currentToken->delete();
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(
                    ['message' => $message],
                    401
                );
            }

            return redirect()->route('login')
                ->withErrors(['login' => $message]);
        }

        return $next($request);
    }
}
