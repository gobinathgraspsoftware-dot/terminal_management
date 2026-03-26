<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        // Get session timeout in minutes from config (default: 60 minutes)
        $timeout = Config::get('session.lifetime', 60);

        // Get last activity timestamp from session
        $lastActivity = $request->session()->get('last_activity_time');

        // If last activity exists, check if session has timed out
        if ($lastActivity && (time() - $lastActivity) > ($timeout * 60)) {
            // Session has timed out
            $user = Auth::user();

            // ============================================================
            // FIX: Wrap activity log in try-catch
            // When remember-me re-authenticates on a fresh session,
            // the activity log call could fail and throw an exception,
            // which causes a 500 error that gets caught by the exception
            // handler and redirected (302) to login, creating a loop.
            // ============================================================
            try {
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => $request->ip(),
                        'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                        'timeout_minutes' => $timeout,
                    ])
                    ->log('Session timeout - user logged out');
            } catch (\Exception $e) {
                Log::warning('Activity log failed during session timeout: ' . $e->getMessage());
            }

            // Logout the user
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Return appropriate response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session has expired due to inactivity. Please login again.',
                    'timeout' => true,
                    'redirect' => route('login')
                ], 401);
            }

            return redirect()->route('login')
                ->with('warning', 'Your session has expired due to inactivity. Please login again.');
        }

        // Update last activity timestamp
        $request->session()->put('last_activity_time', time());

        return $next($request);
    }
}
