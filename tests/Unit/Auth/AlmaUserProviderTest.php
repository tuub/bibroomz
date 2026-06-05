<?php

covers(App\Auth\AlmaUserProvider::class);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Ixudra\Curl\Facades\Curl;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

beforeEach(fn () => app()['session']->start());

test('system user shadowing skips remote lookup', function () {
    $user = User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $authenticatedUser = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'localuser',
        'password' => 'secret-pass',
    ]);

    expect($authenticatedUser?->id)->toBe($user->id);
});

test('remote user creation marks account as directory backed', function () {
    mockAlmaServiceResponse('<result><code>0</code><email_address>remote@example.org</email_address></result>');

    $authenticatedUser = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'RemoteUser',
        'password' => 'remote-secret',
    ]);

    expect($authenticatedUser)->not->toBeNull();
    expect($authenticatedUser->name)->toBe('remoteuser');
    expect($authenticatedUser->isSystemUser())->toBeFalse();
    expect(Hash::check('Test123!', $authenticatedUser->password))->toBeFalse();
});

test('remote auth updates an existing directory backed user and stores the auth message', function () {
    $user = User::factory()->create([
        'name' => 'RemoteUser',
        'email' => 'old@example.org',
        'password' => Hash::make('old-secret'),
        'is_system_user' => false,
    ]);

    mockAlmaServiceResponse('<result><code>0</code><email_address>fresh@example.org</email_address></result>');

    $authenticatedUser = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remoteuser',
        'password' => 'remote-secret',
    ]);

    expect($authenticatedUser?->id)->toBe($user->id)
        ->and($authenticatedUser?->fresh()->email)->toBe('fresh@example.org')
        ->and(session('auth_message'))->toBe('Logged in!');
});

test('remote auth enables debug logging only when configured', function () {
    config()->set('roomz.auth.api.is_debug', true);
    config()->set('roomz.auth.api.log_file', 'curl-test.log');

    $builder = Mockery::mock();
    $builder->shouldReceive('withData')->once()->andReturnSelf();
    $builder->shouldReceive('withTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withConnectTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->times(4)->andReturnSelf();
    $builder->shouldReceive('enableDebug')
        ->once()
        ->with(storage_path('curl-test.log'))
        ->andReturnSelf();
    $builder->shouldReceive('post')
        ->once()
        ->andReturn('<result><code>1</code></result>');

    Curl::shouldReceive('to')->once()->andReturn($builder);

    $result = buildAlmaProvider()->retrieveByCredentials(['username' => 'remoteuser', 'password' => 'wrong-secret']);
    expect($result)->toBeNull();
});

test('remote auth returns null for malformed responses', function () {
    mockAlmaServiceResponse('not-xml');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remoteuser',
        'password' => 'wrong-secret',
    ]);

    expect($result)->toBeNull();
});

test('provider exposes no op token methods and can retrieve by id', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);
    $provider = buildAlmaProvider();

    expect($provider->retrieveById($user->id)?->id)->toBe($user->id)
        ->and($provider->validateCredentials($user, ['password' => 'secret-pass']))->toBeTrue()
        ->and($provider->retrieveByToken($user->id, 'remember-token'))->toBeNull();

    $provider->updateRememberToken($user, 'remember-token');

    expect(true)->toBeTrue();
});

test('configured local test accounts authenticate as system users', function () {
    config()->set('roomz.test-accounts.is_enabled', true);

    $admin = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.admin.username'),
        'password' => config('roomz.test-accounts.admin.password'),
    ]);
    $testOne = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.test1.username'),
        'password' => config('roomz.test-accounts.test1.password'),
    ]);
    $testTwo = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.test2.username'),
        'password' => config('roomz.test-accounts.test2.password'),
    ]);

    expect($admin?->isSystemUser())->toBeTrue()
        ->and($admin?->isAdmin())->toBeTrue()
        ->and($testOne?->isSystemUser())->toBeTrue()
        ->and($testOne?->isAdmin())->toBeFalse()
        ->and($testTwo?->isSystemUser())->toBeTrue()
        ->and($testTwo?->isAdmin())->toBeFalse();
});

test('system user with wrong password and non debug remote failure returns null', function () {
    $user = User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => $user->name,
        'password' => 'wrong-pass',
    ]);

    expect($result)->toBeNull();
});

test('password rehash only runs when needed or forced', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);
    $provider = buildAlmaProvider();

    $currentHash = $user->password;
    $provider->rehashPasswordIfRequired($user, ['password' => 'secret-pass']);
    $user->refresh();

    expect($user->password)->toBe($currentHash);

    $provider->rehashPasswordIfRequired($user, ['password' => 'new-secret'], true);
    $user->refresh();

    expect(Hash::check('new-secret', $user->password))->toBeTrue();
});
