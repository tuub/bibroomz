<?php

namespace App\Providers;

use App\Auth\AlmaUserProvider;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Policies\HappeningPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
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
        Happening::class => HappeningPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('view-admin-panel', fn (User $user): bool => $user->getPermissions()->isNotEmpty());

        Gate::after(function (User $user): bool {
            if ($user->isAdmin()) {
                return true;
            }

            return false;
        });

        Gate::before(function (User $user, string $ability, array $args): ?bool {
            $institution = collect($args)->first();

            if (! $institution instanceof Institution) {
                // check global permissions
                if ($user->roles->contains(fn (Role $role): bool => $role->hasPermission($ability))) {
                    return true;
                }

                return null;
            }

            // check institution scoped permissions
            if (
                $user->roles->contains(
                    fn (Role $role): bool => $role->hasPermission($ability, $institution)
                )
            ) {
                return true;
            }

            return null;
        });

        Gate::define('viewPulse', fn (User $user): bool => $user->isAdmin());

        Auth::provider('alma', function (Application $app): AlmaUserProvider {
            return new AlmaUserProvider($app->make(Hasher::class));
        });
    }
}
