<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
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

        // Check if user has any of the required roles
        if (!$user->hasAnyRole($roles)) {
            // Log the unauthorized access attempt
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'required_roles' => $roles,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                ])
                ->log('Unauthorized role access attempt');

            // Return appropriate response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'You do not have permission to access this resource.',
                    'required_roles' => $roles,
                ], 403);
            }

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}