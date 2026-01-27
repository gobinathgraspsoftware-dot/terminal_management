<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register StudentApprovalService
        $this->app->singleton(\App\Services\StudentApprovalService::class, function ($app) {
            return new \App\Services\StudentApprovalService(
                $app->make(\App\Services\NotificationService::class),
                $app->make(\App\Services\WhatsAppService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
