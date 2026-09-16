<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
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
