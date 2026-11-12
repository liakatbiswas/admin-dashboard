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

        if ($user && ! $user->is_active) {
            if ($request->hasSession()) {
                if (Auth::guard('web')->id() === $user->getAuthIdentifier()) {
                    Auth::guard('web')->logout();
                }

                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $currentToken = $user->currentAccessToken();
            if ($currentToken && method_exists($currentToken, 'delete')) {
                $currentToken->delete();
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(
                    ['message' => 'Your account has been deactivated.'],
                    401
                );
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}
