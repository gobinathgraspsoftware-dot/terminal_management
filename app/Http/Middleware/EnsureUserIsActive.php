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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Check if user status is active
        if ($user->status !== 'active') {
            // Log the user out
            Auth::logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Return appropriate response based on request type
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your account has been suspended. Please contact administrator.',
                    'redirect' => route('login')
                ], 403);
            }

            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your account has been suspended. Please contact administrator.'
                ]);
        }

        return $next($request);
    }
}