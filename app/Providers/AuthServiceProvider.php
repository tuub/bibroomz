<?php

namespace App\Providers;

use App\Auth\AlmaUserProvider;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Model\Happening' => 'App\Policy\HappeningPolicy'
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('admin', function (User $user) {
            return $user->is_admin === true;
        });

        // Just return true on builtin method
        if (env('AUTH_METHOD') == 'eloquent') {
            return;
        }

        Auth::provider('alma', function ($app, array $config) {
            // Return an instance of Illuminate\Contracts\Auth\UserProvider...
            return new AlmaUserProvider(new User());
        });
    }
}
