<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'redirect' => route('login')
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has any of the required permissions
        if (!$user->hasAnyPermission($permissions)) {
            // Log the unauthorized access attempt
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'required_permissions' => $permissions,
                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ])
                ->log('Unauthorized permission access attempt');

            // Return appropriate response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'You do not have the required permissions to access this resource.',
                    'required_permissions' => $permissions,
                ], 403);
            }

            abort(403, 'You do not have the required permissions to access this resource.');
        }

        return $next($request);
    }
}