<?php

namespace App\Providers;

use App\Auth\AlmaUserProvider;
use App\Models\Institution;
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
        Gate::define('view-admin-panel', function (User $user) {
            if ($user->getPermissions()->isNotEmpty()) {
                return true;
            }
        });

        Gate::after(function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }

            return false;
        });

        Gate::before(function (User $user, string $ability, array $args) {
            $institution = collect($args)->first();

            if (!$institution instanceof Institution) {
                // check global permissions
                if ($user->roles->contains->hasPermission($ability)) {
                    return true;
                }

                return;
            }

            // check institution scoped permissions
            if ($user->roles->contains->hasPermission($ability, $institution)) {
                return true;
            }
        });

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });

        Auth::provider('alma', function () {
            return new AlmaUserProvider(new User());
        });
    }
}
