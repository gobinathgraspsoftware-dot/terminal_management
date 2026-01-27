<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAjaxAuth
{
    /**
     * Handle an incoming request.
     * 
     * This middleware specifically handles AJAX requests to ensure
     * authenticated users remain authenticated throughout AJAX operations.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle AJAX requests
        if (!$request->ajax() && !$request->expectsJson()) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Your session has expired. Please login again.',
                'redirect' => route('login')
            ], 401);
        }

        $user = Auth::user();

        // Check if user is active
        if ($user->status !== 'active') {
            Auth::logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'authenticated' => false,
                'message' => 'Your account has been suspended. Please contact administrator.',
                'redirect' => route('login')
            ], 403);
        }

        // Add authentication status to response headers for client-side handling
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Auth-Status', 'authenticated');
            $response->header('X-User-Role', $user->roles->first()?->name ?? 'unknown');
        }

        return $response;
    }
}