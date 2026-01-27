<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\SessionTimeout;
use App\Http\Middleware\CheckAjaxAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware that runs on every request
        $middleware->web(append: [
            SessionTimeout::class,
            CheckAjaxAuth::class,
        ]);

        // Middleware aliases for use in routes
        $middleware->alias([
            // TMS Custom Middleware
            'active' => EnsureUserIsActive::class,
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'session.timeout' => SessionTimeout::class,
            'ajax.auth' => CheckAjaxAuth::class,
            
            // Spatie Permission Middleware (if you want to use their middleware too)
            'role.spatie' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission.spatie' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Middleware groups
        $middleware->group('admin', [
            'auth',
            'active',
            'role:admin',
        ]);

        $middleware->group('supervisor', [
            'auth',
            'active',
            'role:supervisor',
        ]);

        $middleware->group('technician', [
            'auth',
            'active',
            'role:technician',
        ]);

        // Combined group for admin or supervisor
        $middleware->group('management', [
            'auth',
            'active',
            'role:admin,supervisor',
        ]);

        // Priority middleware (runs first)
        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            SessionTimeout::class,
            EnsureUserIsActive::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'redirect' => route('login')
                ], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 403) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'Access Forbidden.',
                    ], 403);
                }
                
                return response()->view('errors.403', [], 403);
            }
        });
    })->create();