<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * If the user is already authenticated, redirect them to their
     * role-based dashboard instead of allowing access to guest pages.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Get user's primary role
                $role = $user->roles->first()?->name;
                
                // Redirect to role-based dashboard
                $route = match ($role) {
                    'admin' => '/admin/dashboard',
                    'supervisor' => '/supervisor/dashboard',
                    'technician' => '/technician/dashboard',
                    default => '/dashboard',
                };
                
                return redirect($route);
            }
        }

        return $next($request);
    }
}