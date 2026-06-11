<?php

declare(strict_types=1);

use App\Auth\AlmaUserProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Ixudra\Curl\Facades\Curl;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(AlmaUserProvider::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

beforeEach(fn () => app()['session']->start());

function almaTestConfigString(string $key): string
{
    $value = config($key);

    if (! is_string($value)) {
        throw new RuntimeException("Expected string config value for [$key].");
    }

    return $value;
}

function almaInvokeProviderMethod(AlmaUserProvider $provider, string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod($provider, $method);

    return $reflection->invoke($provider, ...$arguments);
}

test('system user shadowing skips remote lookup', function (): void {
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

test('remote user creation marks account as directory backed', function (): void {
    mockAlmaServiceResponse('<result><code>0</code><email_address>remote@example.org</email_address></result>');

    $authenticatedUser = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'RemoteUser',
        'password' => 'remote-secret',
    ]);

    expect($authenticatedUser)->not->toBeNull();
    expect($authenticatedUser?->name)->toBe('remoteuser');
    expect($authenticatedUser?->isSystemUser())->toBeFalse();
    expect(Hash::check('Test123!', (string) $authenticatedUser?->password))->toBeFalse();
});

test('remote auth updates an existing directory backed user and stores the auth message', function (): void {
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
        ->and($authenticatedUser?->fresh()?->email)->toBe('fresh@example.org')
        ->and(session('auth_message'))->toBe('Logged in!');
});

test('remote auth enables debug logging only when configured', function (): void {
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

test('remote auth returns null for malformed responses', function (): void {
    mockAlmaServiceResponse('not-xml');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remoteuser',
        'password' => 'wrong-secret',
    ]);

    expect($result)->toBeNull();
});

test('provider exposes no op token methods and can retrieve by id', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);
    $provider = buildAlmaProvider();

    expect($provider->retrieveById($user->id)?->id)->toBe($user->id)
        ->and($provider->validateCredentials($user, ['password' => 'secret-pass']))->toBeTrue()
        ->and($provider->retrieveByToken($user->id, 'remember-token'))->toBeNull();

    $provider->updateRememberToken($user, 'remember-token');

    // updateRememberToken is a no-op; verify the user still exists unchanged
    expect(User::findOrFail($user->id)->getRememberToken())->toBeEmpty();
});

test('configured local test accounts authenticate as system users', function (): void {
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

test('system user with wrong password and non debug remote failure returns null', function (): void {
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

test('retrieve by id returns null for non-string non-int identifiers', function (): void {
    expect(buildAlmaProvider()->retrieveById(null))->toBeNull()
        ->and(buildAlmaProvider()->retrieveById([]))->toBeNull();
});

test('retrieve by credentials returns null when username or password is missing', function (): void {
    expect(buildAlmaProvider()->retrieveByCredentials(['username' => 'alice']))->toBeNull()
        ->and(buildAlmaProvider()->retrieveByCredentials(['password' => 'secret']))->toBeNull()
        ->and(buildAlmaProvider()->retrieveByCredentials([]))->toBeNull();
});

test('config bool accepts string boolean values in remote auth flow', function (): void {
    config()->set('roomz.auth.api.is_debug', 'false');
    mockAlmaServiceResponse('<result><code>0</code><email_address>user@example.test</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'config-string-bool-user',
        'password' => 'test-pass',
    ]);

    expect($result)->not->toBeNull();
});

test('config float handles numeric string timeout values', function (): void {
    config()->set('roomz.auth.api.timeout', '5.5');

    mockAlmaServiceResponse('<result><code>0</code><email_address>user@example.test</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'float-string-user',
        'password' => 'test-pass',
    ]);

    expect($result)->not->toBeNull();
});

test('password rehash only runs when needed or forced', function (): void {
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

    // force=true but no password key — early return without rehash
    $hashBefore = $user->password;
    $provider->rehashPasswordIfRequired($user, [], true);
    $user->refresh();

    expect($user->password)->toBe($hashBefore);
});

test('findUserByLoginName returns null for empty username', function (): void {
    // Utility::normalizeLoginName('') returns null — early return
    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => '',
        'password' => 'test',
    ]);

    expect($result)->toBeNull();
});

test('upsertAuthenticatedUser catches db exception and returns null when user creation fails', function (): void {
    // Simulate a DB failure during User::create() via the Eloquent creating event
    Event::listen(
        'eloquent.creating: '.User::class,
        static function (): void {
            throw new QueryException('sqlite', 'insert', [], new PDOException('Simulated DB failure'));
        },
    );

    try {
        mockAlmaServiceResponse('<result><code>0</code><email_address>newuser@test.example</email_address></result>');
        $result = buildAlmaProvider()->retrieveByCredentials(['username' => 'brand-new-user', 'password' => 'pass']);
        expect($result)->toBeNull();
    } finally {
        // Always remove the throwing listener so subsequent tests are unaffected
        Event::forget('eloquent.creating: '.User::class);
    }
});

test('getLocalUserInfo skips accounts with missing config and returns null when credentials never match', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);
    // Remove email for admin account — buildTestAccountUserData returns null — continue branch
    config()->set('roomz.test-accounts.admin.email', null);

    // Provide credentials that don't match any configured test account
    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'nobody',
        'password' => 'wrong',
    ]);

    // All accounts either skipped (null data) or not matched — final return null
    expect($result)->toBeNull();
});

test('getRemoteUserInfo returns null when endpoint is not configured', function (): void {
    config()->set('roomz.auth.api.endpoint', null);

    // Non-existing user, test-accounts disabled — reaches getRemoteUserInfo — endpoint null — null
    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remote-nobody',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

test('getRemoteUserInfo returns null when preg_replace fails with a PCRE catastrophic error', function (): void {
    // The namespace override in tests/NamespaceOverrides.php intercepts preg_replace() calls
    // from App\Auth namespace and returns null when this flag is set
    $GLOBALS['__test_preg_replace_returns_null'] = true;

    try {
        mockAlmaServiceResponse('<result><code>0</code></result>');
        $result = buildAlmaProvider()->retrieveByCredentials(['username' => 'remote-user', 'password' => 'pass']);
        expect($result)->toBeNull();
    } finally {
        unset($GLOBALS['__test_preg_replace_returns_null']);
    }
});

test('getRemoteUserInfo returns null when xml result is not an array', function (): void {
    // <result> contains text not child elements — XmlToArray gives a string, not array
    mockAlmaServiceResponse('<result>error</result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remote-user',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

test('getRemoteUserInfo returns null when code is not a scalar', function (): void {
    // Nested <code> element — parsed as array, not int/string
    mockAlmaServiceResponse('<result><code><item>0</item></code></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remote-user',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

test('getRemoteUserInfo returns null when email address is missing in successful response', function (): void {
    // code=0 but no email_address element
    mockAlmaServiceResponse('<result><code>0</code></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remote-user',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

test('buildTestAccountUserData returns null when account email config is null', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);
    config()->set('roomz.test-accounts.admin.email', null);

    // Provide admin credentials — buildTestAccountUserData returns null for admin — skipped
    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.admin.username'),
        'password' => config('roomz.test-accounts.admin.password'),
    ]);

    // Admin account is skipped; other accounts don't match these credentials
    expect($result)->toBeNull();
});

test('buildTestAccountUserData returns null when username normalizes to null', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);
    // Empty-string username — normalizeLoginName('') — null — return null in buildTestAccountUserData
    config()->set('roomz.test-accounts.admin.username', '');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'any-user',
        'password' => 'any-pass',
    ]);

    expect($result)->toBeNull();
});

test('configBool returns correct bool for integer config values', function (): void {
    config()->set('roomz.auth.api.is_debug', 1);
    // No log_file — enableDebug branch is skipped; mock doesn't need to expect it
    config()->set('roomz.auth.api.log_file', null);

    mockAlmaServiceResponse('<result><code>0</code><email_address>user@test.example</email_address></result>');

    // is_debug=1 (int) — configBool hits the is_int() branch — returns true
    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'int-config-user',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull();
});

test('configBool returns false for non-scalar config values', function (): void {
    // Set is_debug to an array — configBool hits the final return false
    config()->set('roomz.auth.api.is_debug', []);

    mockAlmaServiceResponse('<result><code>0</code><email_address>user@test.example</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'array-config-user',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull();
});

test('configFloat returns 0.0 for non-numeric config values', function (): void {
    // Set timeout to an array — configFloat returns 0.0
    config()->set('roomz.auth.api.timeout', []);

    mockAlmaServiceResponse('<result><code>0</code><email_address>user@test.example</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'array-float-user',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// upsertAuthenticatedUser – update path: email and last_login (lines 103-106)
// ---------------------------------------------------------------------------

test('upsertAuthenticatedUser updates email and last_login on existing user', function (): void {
    $before = Carbon::now()->subMinute();

    $user = User::factory()->create([
        'name' => 'UpdateEmailUser',
        'email' => 'old-email@example.org',
        'is_system_user' => false,
    ]);

    mockAlmaServiceResponse('<result><code>0</code><email_address>new-email@example.org</email_address></result>');

    buildAlmaProvider()->retrieveByCredentials([
        'username' => 'updateemailuser',
        'password' => 'any-pass',
    ]);

    $fresh = $user->fresh();

    expect($fresh?->email)->toBe('new-email@example.org')
        ->and($fresh?->last_login)->not->toBeNull()
        ->and(Carbon::parse($fresh?->last_login)->greaterThanOrEqualTo($before))->toBeTrue();
});

test('upsertAuthenticatedUser preserves is_system_user false on directory-backed user after update', function (): void {
    $user = User::factory()->create([
        'name' => 'SystemFlagUser',
        'is_system_user' => false,
    ]);

    mockAlmaServiceResponse('<result><code>0</code><email_address>flag@example.org</email_address></result>');

    buildAlmaProvider()->retrieveByCredentials([
        'username' => 'systemflaguser',
        'password' => 'any-pass',
    ]);

    expect($user->fresh()?->is_system_user)->toBeFalse();
});

// ---------------------------------------------------------------------------
// upsertAuthenticatedUser – create path: name, is_admin, is_system_user (lines 109-116)
// ---------------------------------------------------------------------------

test('upsertAuthenticatedUser creates user with correct name email is_admin and is_system_user', function (): void {
    mockAlmaServiceResponse('<result><code>0</code><email_address>created@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'BrandNewRemoteUser',
        'password' => 'secret',
    ]);

    expect($result)->not->toBeNull()
        ->and($result?->name)->toBe('brandnewremoteuser')
        ->and($result?->email)->toBe('created@example.org')
        ->and($result?->is_admin)->toBeFalse()
        ->and($result?->is_system_user)->toBeFalse()
        ->and(is_string($result?->password))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Log::error called on DB exception
// ---------------------------------------------------------------------------

test('upsertAuthenticatedUser logs the exception when user creation fails', function (): void {
    Log::shouldReceive('info')->zeroOrMoreTimes();
    Log::shouldReceive('error')->once();

    Event::listen(
        'eloquent.creating: '.User::class,
        static function (): void {
            throw new QueryException('sqlite', 'insert', [], new PDOException('Simulated DB failure'));
        },
    );

    try {
        mockAlmaServiceResponse('<result><code>0</code><email_address>logerr@test.example</email_address></result>');
        $result = buildAlmaProvider()->retrieveByCredentials(['username' => 'log-error-user', 'password' => 'pass']);
        expect($result)->toBeNull();
    } finally {
        Event::forget('eloquent.creating: '.User::class);
    }
});

// ---------------------------------------------------------------------------
// findUserByLoginName – strtolower
// ---------------------------------------------------------------------------

test('findUserByLoginName matches user regardless of credential case', function (): void {
    $user = User::factory()->create(['name' => 'MixedCaseUser', 'is_system_user' => false]);

    mockAlmaServiceResponse('<result><code>0</code><email_address>mixed@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'MIXEDCASEUSER',
        'password' => 'pass',
    ]);

    expect($result?->id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// getLocalUserInfo – is_enabled early return
// ---------------------------------------------------------------------------

test('getLocalUserInfo returns null when test accounts disabled and falls through to remote', function (): void {
    config()->set('roomz.test-accounts.is_enabled', false);

    // Remote lookup succeeds — proves getLocalUserInfo returned null instead of crashing
    mockAlmaServiceResponse('<result><code>0</code><email_address>remote@fallthrough.test</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.admin.username'),
        'password' => config('roomz.test-accounts.admin.password'),
    ]);

    // Result comes from remote, not local test account
    expect($result)->not->toBeNull()
        ->and($result?->is_system_user)->toBeFalse();
});

// ---------------------------------------------------------------------------
// getLocalUserInfo – continue branch and credential checks
// ---------------------------------------------------------------------------

test('getLocalUserInfo skips first account via continue and matches second account', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);
    // Null email for admin — buildTestAccountUserData returns null — continue
    config()->set('roomz.test-accounts.admin.email', null);

    // test1 credentials should still work
    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.test1.username'),
        'password' => config('roomz.test-accounts.test1.password'),
    ]);

    expect($result)->not->toBeNull()
        ->and($result?->isSystemUser())->toBeTrue()
        ->and($result?->isAdmin())->toBeFalse();
});

test('getLocalUserInfo returns null when username matches but password does not', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.admin.username'),
        'password' => 'definitely-wrong-password',
    ]);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// getSystemUserInfo – BooleanAndToBooleanOr / TrueToFalse (lines 217-227)
// ---------------------------------------------------------------------------

test('getSystemUserInfo returns null when system user password is wrong', function (): void {
    User::factory()->create([
        'name' => 'SysUserWrongPw',
        'password' => Hash::make('correct-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'sysuserwrongpw',
        'password' => 'wrong-pass',
    ]);

    expect($result)->toBeNull();
});

test('getSystemUserInfo returns authenticated user with is_system_user true', function (): void {
    $user = User::factory()->create([
        'name' => 'SysUserVerify',
        'password' => Hash::make('correct-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'sysuserverify',
        'password' => 'correct-pass',
    ]);

    expect($result?->isSystemUser())->toBeTrue()
        ->and($result?->id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// getRemoteUserInfo – exact CURL option values (lines 255-258)
// ---------------------------------------------------------------------------

test('getRemoteUserInfo sends correct CURL options', function (): void {
    $builder = Mockery::mock();
    $builder->shouldReceive('withData')->once()->andReturnSelf();
    $builder->shouldReceive('withTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withConnectTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->with('SSL_VERIFYHOST', 2)->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->with('SSL_VERIFYPEER', 1)->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->with('POST', 1)->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->with('RETURNTRANSFER', true)->once()->andReturnSelf();
    $builder->shouldReceive('post')->once()->andReturn('<result><code>0</code><email_address>u@test.com</email_address></result>');

    Curl::shouldReceive('to')->once()->andReturn($builder);

    $result = buildAlmaProvider()->retrieveByCredentials(['username' => 'testcurloptions', 'password' => 'pass']);
    expect($result)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// getRemoteUserInfo – response validation
// ---------------------------------------------------------------------------

test('getRemoteUserInfo returns null for empty string response', function (): void {
    mockAlmaServiceResponse('');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remote-user',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

test('getRemoteUserInfo returns null when response does not start with result tag', function (): void {
    mockAlmaServiceResponse('{"error":"unexpected json"}');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'remote-user',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// getRemoteUserInfo – non-zero code (lines 297-300)
// ---------------------------------------------------------------------------

test('getRemoteUserInfo returns null when response code is non-zero', function (): void {
    Log::shouldReceive('info')->atLeast()->once();

    mockAlmaServiceResponse('<result><code>1</code><email_address>user@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'wrong-credentials-user',
        'password' => 'wrong-pass',
    ]);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// getRemoteUserInfo – success path return array keys (lines 312-318)
// ---------------------------------------------------------------------------

test('getRemoteUserInfo success returns user with all expected fields set', function (): void {
    mockAlmaServiceResponse('<result><code>0</code><email_address>fields@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'FieldsUser',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull()
        ->and($result?->name)->toBe('fieldsuser')
        ->and($result?->email)->toBe('fields@example.org')
        ->and($result?->is_admin)->toBeFalse()
        ->and($result?->is_system_user)->toBeFalse()
        ->and(is_string($result?->password) && $result->password !== '')->toBeTrue();
});

// ---------------------------------------------------------------------------
// buildTestAccountUserData – one of username/password/email null
// ---------------------------------------------------------------------------

test('buildTestAccountUserData returns null when only password is null', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);
    // username and email are set but password is null
    config()->set('roomz.test-accounts.admin.password', null);

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.admin.username'),
        'password' => 'does-not-matter',
    ]);

    expect($result)->toBeNull();
});

test('buildTestAccountUserData returns null when only email is null', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);
    config()->set('roomz.test-accounts.admin.email', null);

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => config('roomz.test-accounts.admin.username'),
        'password' => 'does-not-matter',
    ]);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// configBool – integer zero should return false
// ---------------------------------------------------------------------------

test('configBool returns false for integer zero config value', function (): void {
    config()->set('roomz.auth.api.is_debug', 0);

    mockAlmaServiceResponse('<result><code>0</code><email_address>user@test.example</email_address></result>');

    // is_debug=0 — configBool int branch — $value !== 0 — false — debug branch skipped
    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'zero-int-config-user',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// configFloat – float config value
// ---------------------------------------------------------------------------

test('configFloat handles actual float config value', function (): void {
    config()->set('roomz.auth.api.timeout', 7.5);

    mockAlmaServiceResponse('<result><code>0</code><email_address>user@test.example</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'float-config-user',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// CoalesceRemoveLeft – $existingUser?->isSystemUser()
// ---------------------------------------------------------------------------

test('existing non-system user falls through to remote lookup', function (): void {
    // existingUser exists but is NOT a system user — goes to getLocalUserInfo/getRemoteUserInfo path
    User::factory()->create([
        'name' => 'NonSysExisting',
        'is_system_user' => false,
    ]);

    mockAlmaServiceResponse('<result><code>0</code><email_address>nonsys@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'nonsysexisting',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull()
        ->and($result?->is_system_user)->toBeFalse();
});

test('null existing user falls through to remote lookup', function (): void {
    // No user in DB — existingUser is null — $existingUser?->isSystemUser() is null (falsy)
    mockAlmaServiceResponse('<result><code>0</code><email_address>newremote@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'completely-new-user',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull()
        ->and($result?->is_system_user)->toBeFalse();
});

test('local test account data wins over stored system user data when both credentials match', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);

    $existingUser = User::factory()->create([
        'name' => config('roomz.test-accounts.admin.username'),
        'email' => 'stored-system@example.org',
        'password' => Hash::make(almaTestConfigString('roomz.test-accounts.admin.password')),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => almaTestConfigString('roomz.test-accounts.admin.username'),
        'password' => almaTestConfigString('roomz.test-accounts.admin.password'),
    ]);

    expect($result?->id)->toBe($existingUser->id)
        ->and($result?->fresh()?->email)->toBe(config('roomz.test-accounts.admin.email'))
        ->and($result?->fresh()?->is_system_user)->toBeTrue();
});

test('remote user creation stores last_login for new directory backed users', function (): void {
    $before = Carbon::now()->subSecond();

    mockAlmaServiceResponse('<result><code>0</code><email_address>created-last-login@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'created-last-login-user',
        'password' => 'secret',
    ]);

    expect($result)->not->toBeNull()
        ->and($result?->fresh()?->last_login)->not->toBeNull()
        ->and(Carbon::parse($result?->fresh()?->last_login)->greaterThanOrEqualTo($before))->toBeTrue();
});

test('system user auth rejects users whose email is not a string', function (): void {
    User::factory()->create([
        'name' => 'SystemUserWithoutEmail',
        'email' => null,
        'password' => Hash::make('correct-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'systemuserwithoutemail',
        'password' => 'correct-pass',
    ]);

    expect($result)->toBeNull();
});

test('remote auth sends the exact credential payload expected by alma', function (): void {
    $builder = Mockery::mock();
    $builder->shouldReceive('withData')
        ->once()
        ->with([
            'uid' => 'payload-user',
            'pw' => 'payload-secret',
        ])
        ->andReturnSelf();
    $builder->shouldReceive('withTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withConnectTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->times(4)->andReturnSelf();
    $builder->shouldReceive('post')
        ->once()
        ->andReturn('<result><code>0</code><email_address>payload@example.org</email_address></result>');

    Curl::shouldReceive('to')->once()->andReturn($builder);

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'payload-user',
        'password' => 'payload-secret',
    ]);

    expect($result?->email)->toBe('payload@example.org');
});

test('configBool returns true for true strings and false for unknown strings', function (): void {
    $provider = buildAlmaProvider();

    config()->set('roomz.auth.api.is_debug', 'true');
    expect(almaInvokeProviderMethod($provider, 'configBool', 'roomz.auth.api.is_debug'))->toBeTrue();

    config()->set('roomz.auth.api.is_debug', 'definitely-not-a-bool');
    expect(almaInvokeProviderMethod($provider, 'configBool', 'roomz.auth.api.is_debug'))->toBeFalse();
});

test('configFloat normalizes ints floats strings and non numeric values exactly', function (): void {
    $provider = buildAlmaProvider();

    config()->set('roomz.auth.api.timeout', 3);
    expect(almaInvokeProviderMethod($provider, 'configFloat', 'roomz.auth.api.timeout'))->toBe(3.0);

    config()->set('roomz.auth.api.timeout', 7.5);
    expect(almaInvokeProviderMethod($provider, 'configFloat', 'roomz.auth.api.timeout'))->toBe(7.5);

    config()->set('roomz.auth.api.timeout', '5.5');
    expect(almaInvokeProviderMethod($provider, 'configFloat', 'roomz.auth.api.timeout'))->toBe(5.5);

    config()->set('roomz.auth.api.timeout', 'not-a-number');
    expect(almaInvokeProviderMethod($provider, 'configFloat', 'roomz.auth.api.timeout'))->toBe(0.0);
});

test('jsonEncodeForLog returns null string when json encoding fails', function (): void {
    $provider = buildAlmaProvider();
    $recursive = [];
    $recursive['self'] = &$recursive;

    expect(almaInvokeProviderMethod($provider, 'jsonEncodeForLog', $recursive))->toBe('null')
        ->and(almaInvokeProviderMethod($provider, 'jsonEncodeForLog', ['uid' => 'alma.user']))->toBe('{"uid":"alma.user"}');
});

test('local test account auth updates an existing directory user to system user', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);

    $user = User::factory()->create([
        'name' => config('roomz.test-accounts.test1.username'),
        'email' => 'directory@example.org',
        'password' => Hash::make('directory-pass'),
        'is_system_user' => false,
    ]);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => almaTestConfigString('roomz.test-accounts.test1.username'),
        'password' => almaTestConfigString('roomz.test-accounts.test1.password'),
    ]);

    expect($result?->id)->toBe($user->id)
        ->and($result?->fresh()?->is_system_user)->toBeTrue();
});

test('findUserByLoginName lowercases the lookup when normalization is disabled', function (): void {
    config()->set('roomz.user.login_name_normalization_method', 0);

    $user = User::factory()->create([
        'name' => 'MixedCaseSystemUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'MIXEDCASESYSTEMUSER',
        'password' => 'secret-pass',
    ]);

    expect($result?->id)->toBe($user->id);
});

test('getSystemUserInfo returns the exact authenticated payload', function (): void {
    $user = User::factory()->create([
        'name' => 'system-payload-user',
        'email' => 'system-payload@example.org',
        'password' => Hash::make('secret-pass'),
        'is_admin' => true,
        'is_system_user' => true,
    ]);

    $payload = almaInvokeProviderMethod(buildAlmaProvider(), 'getSystemUserInfo', [
        'username' => $user->name,
        'password' => 'secret-pass',
    ]);

    expect($payload)->toBe([
        'name' => $user->name,
        'email' => $user->email,
        'password' => $user->password,
        'is_admin' => true,
        'is_system_user' => true,
    ]);
});

test('getSystemUserInfo returns null when the user lookup does not resolve to a user model', function (): void {
    expect(almaInvokeProviderMethod(buildAlmaProvider(), 'getSystemUserInfo', [
        'username' => 'missing-user',
        'password' => 'secret-pass',
    ]))->toBeNull();
});

test('getRemoteUserInfo returns null without contacting curl when endpoint is missing', function (): void {
    config()->set('roomz.auth.api.endpoint', null);

    Curl::shouldReceive('to')->never();

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'missing-endpoint-user',
        'password' => 'secret-pass',
    ]);

    expect($result)->toBeNull();
});

test('malformed remote auth responses log the exact failure messages', function (): void {
    Log::shouldReceive('info')->once()->with('ALMA: failed call to API for user: "broken-user"');
    Log::shouldReceive('info')->once()->with('not-xml');

    mockAlmaServiceResponse('not-xml');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'broken-user',
        'password' => 'secret-pass',
    ]);

    expect($result)->toBeNull();
});

test('non string remote auth responses are logged and rejected', function (): void {
    $builder = Mockery::mock();
    $builder->shouldReceive('withData')->once()->andReturnSelf();
    $builder->shouldReceive('withTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withConnectTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->times(4)->andReturnSelf();
    $builder->shouldReceive('post')->once()->andReturn(['error' => 'bad-response']);

    Curl::shouldReceive('to')->once()->andReturn($builder);

    Log::shouldReceive('info')->once()->with('ALMA: failed call to API for user: "array-response-user"');
    Log::shouldReceive('info')->once()->with('{"error":"bad-response"}');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'array-response-user',
        'password' => 'secret-pass',
    ]);

    expect($result)->toBeNull();
});

test('successful remote auth accepts whitespace-heavy xml and logs the exact success message', function (): void {
    Log::shouldReceive('info')->once()->with('ALMA: Successful login for user: "spaced-user"');

    mockAlmaServiceResponse("<result>\n  <code>0</code>\n  <email_address>spaced@example.org</email_address>\n</result>");

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'spaced-user',
        'password' => 'secret-pass',
    ]);

    expect($result?->email)->toBe('spaced@example.org');
});

test('non zero alma response logs the exact wrong credentials message', function (): void {
    Log::shouldReceive('info')->once()->with('ALMA: Wrong username/password for user: "wrong-user"');

    mockAlmaServiceResponse('<result><code>1</code><email_address>wrong@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'wrong-user',
        'password' => 'secret-pass',
    ]);

    expect($result)->toBeNull();
});

test('buildTestAccountUserData returns null when only the configured password is missing', function (): void {
    config()->set('roomz.test-accounts.admin.password', null);

    expect(almaInvokeProviderMethod(buildAlmaProvider(), 'buildTestAccountUserData', 'admin', true))->toBeNull();
});

test('getLocalUserInfo rejects when username matches but password does not match', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);

    $adminUsername = config('roomz.test-accounts.admin.username');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => $adminUsername,
        'password' => 'completely-wrong-password-that-will-never-match',
    ]);

    expect($result)->toBeNull();
});

test('getLocalUserInfo rejects when password matches but username does not match', function (): void {
    config()->set('roomz.test-accounts.is_enabled', true);

    $adminPassword = config('roomz.test-accounts.admin.password');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'non-matching-username-12345',
        'password' => $adminPassword,
    ]);

    expect($result)->toBeNull();
});

test('getRemoteUserInfo returns null when curl post returns empty string', function (): void {
    $builder = Mockery::mock();
    $builder->shouldReceive('withData')->once()->andReturnSelf();
    $builder->shouldReceive('withTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withConnectTimeout')->once()->andReturnSelf();
    $builder->shouldReceive('withOption')->times(4)->andReturnSelf();
    $builder->shouldReceive('post')->once()->andReturn('');

    Curl::shouldReceive('to')->once()->andReturn($builder);

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'empty-response-user',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

test('getRemoteUserInfo returns null when result is not an array', function (): void {
    mockAlmaServiceResponse('<result>plain text content not xml children</result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'result-not-array-user',
        'password' => 'pass',
    ]);

    expect($result)->toBeNull();
});

test('getRemoteUserInfo succeeds when code is integer zero', function (): void {
    mockAlmaServiceResponse('<result><code>0</code><email_address>intcode@example.org</email_address></result>');

    $result = buildAlmaProvider()->retrieveByCredentials([
        'username' => 'int-code-user',
        'password' => 'pass',
    ]);

    expect($result)->not->toBeNull()
        ->and($result?->email)->toBe('intcode@example.org');
});

test('configBool returns true for integer one and false for integer zero', function (): void {
    $provider = buildAlmaProvider();

    config()->set('roomz.auth.api.is_debug', 1);
    expect(almaInvokeProviderMethod($provider, 'configBool', 'roomz.auth.api.is_debug'))->toBeTrue();

    config()->set('roomz.auth.api.is_debug', 0);
    expect(almaInvokeProviderMethod($provider, 'configBool', 'roomz.auth.api.is_debug'))->toBeFalse();

    config()->set('roomz.auth.api.is_debug', 42);
    expect(almaInvokeProviderMethod($provider, 'configBool', 'roomz.auth.api.is_debug'))->toBeTrue();
});

test('configFloat returns a float type for integer config value', function (): void {
    $provider = buildAlmaProvider();

    config()->set('roomz.auth.api.timeout', 5);
    $result = almaInvokeProviderMethod($provider, 'configFloat', 'roomz.auth.api.timeout');

    expect($result)->toBe(5.0)
        ->and(is_float($result))->toBeTrue();
});

test('configFloat returns zero for non-numeric string keeping string-and-numeric branch exclusive', function (): void {
    $provider = buildAlmaProvider();

    config()->set('roomz.auth.api.timeout', 'not-a-number');
    $result = almaInvokeProviderMethod($provider, 'configFloat', 'roomz.auth.api.timeout');

    expect($result)->toBe(0.0);
});

test('configFloat returns float for numeric string', function (): void {
    $provider = buildAlmaProvider();

    config()->set('roomz.auth.api.timeout', '3.14');
    $result = almaInvokeProviderMethod($provider, 'configFloat', 'roomz.auth.api.timeout');

    expect($result)->toBe(3.14)
        ->and(is_float($result))->toBeTrue();
});
