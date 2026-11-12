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

        if ($user && (! $user->is_active || $staleSession)) {
            $message = $user->is_active ? __('Your session has expired. Please sign in again.') : __('Your account has been deactivated.');
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
                ->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
