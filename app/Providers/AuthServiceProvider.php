<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Auth\AlmaUserProvider;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

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
