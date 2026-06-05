<?php

use App\Auth\AlmaUserProvider;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;

function grantAdminPermission(User $user, Institution $institution, string $permissionKey): void
{
    $permission = Permission::firstWhere('key', $permissionKey);
    $role = Role::create(['name' => Utility::getTranslatable($permissionKey)]);
    $role->permissions()->attach($permission);
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);
}

function mockLoginAlmaResponse(string $response): void
{
    $builder = Mockery::mock();
    $builder->shouldReceive('withData')->once()->andReturnSelf();
    $builder->shouldReceive('withTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withConnectTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->times(4)->andReturnSelf();
    $builder->shouldReceive('post')->once()->andReturn($response);

    Curl::shouldReceive('to')->once()->andReturn($builder);
}

function buildAlmaProvider(): AlmaUserProvider
{
    return new AlmaUserProvider(app(Hasher::class));
}

function mockAlmaServiceResponse(string $response): void
{
    $builder = Mockery::mock();
    $builder->shouldReceive('withData')->once()->andReturnSelf();
    $builder->shouldReceive('withTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withConnectTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->times(4)->andReturnSelf();
    $builder->shouldReceive('post')->once()->andReturn($response);

    Curl::shouldReceive('to')->once()->andReturn($builder);
}

function buildAdminFormRequest(string $class, array $data, User $user)
{
    return buildFormRequest($class, $data, $user);
}

function buildFormRequest(string $class, array $data, ?User $user = null)
{
    $request = $class::create('/', 'POST', $data);
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());

    return $request;
}

function buildRoutedFormRequest(
    string $class,
    string $method,
    string $uri,
    array $data,
    ?User $user = null,
) {
    $request = $class::create($uri, $method, $data);
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());

    $route = app('router')->getRoutes()->match(Request::create($uri, $method, $data));
    $request->setRouteResolver(fn () => $route);

    return $request;
}
