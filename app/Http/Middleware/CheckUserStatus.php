<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user is locked
            if ($user->isLocked()) {
                Auth::logout();
                return redirect()->route('login')
                    ->withErrors(['email' => 'Your account is temporarily locked. Please try again later.']);
            }

            // Check user status
            if ($user->status !== 'active') {
                Auth::logout();
                return redirect()->route('login')
                    ->withErrors(['email' => 'Your account is not active. Please contact administrator.']);
            }
        }

        return $next($request);
    }
}
