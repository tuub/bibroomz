<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        // Telescope is a development dependency, so deployments that install
        // production dependencies only do not ship it. Registering the
        // application's provider unconditionally would fatal on boot there.
        if (class_exists(TelescopeApplicationServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $buildDirectory = config('roomz.frontend.build_directory');

        if (is_string($buildDirectory) && $buildDirectory !== '') {
            $this->app->make(Vite::class)->useBuildDirectory($buildDirectory);
        }
    }
}
