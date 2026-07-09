<?php

namespace App\Auth;

use App\Exceptions\AlmaNoEmailException;
use App\Library\Utility;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;
use Vyuldashev\XmlToArray\XmlToArray;

/*
 * https://stackoverflow.com/questions/53898804/what-is-authserviceprovider-in-laravel
 * https://stackoverflow.com/questions/45024429/how-to-add-a-custom-user-provider-in-laravel-5-4
 * https://stackoverflow.com/questions/33331421/custom-user-authentication-base-on-the-response-of-an-api-call
 * https://stackoverflow.com/questions/54706877/how-to-create-a-custom-auth-guard-provider-for-laravel-5-7
 * https://gist.github.com/paulofreitas/08ea4f2f09102df8630b8a3c8d7a41bb
 * http://semantic-portal.net/concept:794
 */

/**
 * @phpstan-type Credentials array{username: string, password: string}
 * @phpstan-type AuthUserData array{
 *     name: string,
 *     email: string,
 *     password: string,
 *     is_admin: bool,
 *     is_system_user: bool
 * }
 */
class AlmaUserProvider implements UserProvider
{
    public function __construct(private readonly Hasher $hasher) {}

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     */
    public function retrieveById($identifier): ?User
    {
        if (! is_int($identifier) && ! is_string($identifier)) {
            return null;
        }

        return User::find($identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?User
    {
        $resolvedCredentials = $this->extractCredentials($credentials);

        if ($resolvedCredentials === null) {
            return null;
        }

        $existingUser = $this->findUserByLoginName($resolvedCredentials['username']);

        if ($existingUser?->isSystemUser()) {
            $userData = $this->getLocalUserInfo($resolvedCredentials)
                ?? $this->getSystemUserInfo($resolvedCredentials);

            if ($userData === null) {
                return null;
            }

            return $this->upsertAuthenticatedUser($userData, $existingUser);
        }

        $userData = $this->getLocalUserInfo($resolvedCredentials);

        if ($userData !== null) {
            return $this->upsertAuthenticatedUser($userData, $existingUser);
        }

        $userData = $this->getRemoteUserInfo($resolvedCredentials);

        if ($userData === null) {
            return null;
        }

        return $this->upsertAuthenticatedUser($userData, $existingUser);
    }

    /**
     * @param  AuthUserData  $userData
     */
    private function upsertAuthenticatedUser(array $userData, ?User $user): ?User
    {
        if ($user instanceof User) {
            $user->update([
                'email' => $userData['email'],
                'last_login' => Carbon::now(),
                'is_system_user' => $userData['is_system_user'],
            ]);
        } else {
            try {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'is_admin' => $userData['is_admin'],
                    'is_system_user' => $userData['is_system_user'],
                    'last_login' => Carbon::now(),
                ]);
            } catch (Exception $exception) {
                Log::error($exception);

                return null;
            }
        }

        session()->put([
            'auth_message' => 'Logged in!',
        ]);

        return $user;
    }

    private function findUserByLoginName(string $loginName): ?User
    {
        /** @var string $normalizedLoginName */
        $normalizedLoginName = Utility::normalizeLoginName($loginName);

        return User::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($normalizedLoginName)])
            ->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return true;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  string  $token
     */
    public function updateRememberToken(Authenticatable $user, $token): void {}

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     */
    public function retrieveByToken($identifier, $token): ?User
    {
        return null;
    }

    /**
     * @param  Credentials  $credentials
     * @return AuthUserData|null
     */
    private function getLocalUserInfo(array $credentials): ?array
    {
        if (! $this->configBool('roomz.test-accounts.is_enabled')) {
            return null;
        }

        $accounts = [
            'admin' => true,
            'test1' => false,
            'test2' => false,
        ];

        foreach ($accounts as $accountKey => $isAdmin) {
            $userData = $this->buildTestAccountUserData($accountKey, $isAdmin);

            if ($userData === null) {
                continue;
            }

            $username = $this->configString("roomz.test-accounts.$accountKey.username");
            $password = $this->configString("roomz.test-accounts.$accountKey.password");

            if (
                $username !== null
                && $password !== null
                && $credentials['username'] === $username
                && $credentials['password'] === $password
            ) {
                return $userData;
            }
        }

        return null;
    }

    /**
     * @param  Credentials  $credentials
     * @return AuthUserData|null
     */
    private function getSystemUserInfo(array $credentials): ?array
    {
        $user = $this->findUserByLoginName($credentials['username']);

        if (
            $user instanceof User
            && is_string($user->email)
            && $user->isSystemUser()
            && Hash::check($credentials['password'], $user->password)
        ) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'is_admin' => $user->is_admin,
                'is_system_user' => true,
            ];
        }

        return null;
    }

    /**
     * @param  Credentials  $credentials
     * @return AuthUserData|null
     */
    private function getRemoteUserInfo(array $credentials): ?array
    {
        $endpoint = $this->configString('roomz.auth.api.endpoint');

        if ($endpoint === null) {
            return null;
        }

        $requestCredentials = [
            'uid' => $credentials['username'],
            'pw' => $credentials['password'],
        ];

        $curl = Curl::to($endpoint)
            ->withData($requestCredentials)
            ->withTimeout($this->configFloat('roomz.auth.api.timeout'))
            ->withConnectTimeout($this->configFloat('roomz.auth.api.timeout'))
            ->withOption('SSL_VERIFYHOST', 2)
            ->withOption('SSL_VERIFYPEER', 1)
            ->withOption('POST', 1)
            ->withOption('RETURNTRANSFER', true);

        if ($this->configBool('roomz.auth.api.is_debug')) {
            $logFile = $this->configString('roomz.auth.api.log_file');

            if ($logFile !== null) {
                $curl->enableDebug(storage_path($logFile));
            }
        }

        $response = $curl->post();

        if (! is_string($response) || $response === '' || ! str_starts_with($response, '<result')) {
            Log::info('ALMA: failed call to API for user: '.$this->jsonEncodeForLog($requestCredentials['uid']));
            Log::info(is_string($response) ? $response : $this->jsonEncodeForLog($response));

            return null;
        }

        $cleanResponse = preg_replace('/[\n\r]|\s{2,}/', '', $response);

        if (! is_string($cleanResponse)) {
            return null;
        }

        $xml = XmlToArray::convert($cleanResponse);

        $result = $xml['result'] ?? null;

        if (! is_array($result)) {
            return null;
        }

        $code = $result['code'] ?? null;

        if (! is_int($code) && ! is_string($code)) {
            return null;
        }

        if ((string) $code !== '0') {
            Log::info('ALMA: Wrong username/password for user: '.$this->jsonEncodeForLog($requestCredentials['uid']));

            return null;
        }

        $normalizedName = Utility::normalizeLoginName($requestCredentials['uid']);

        if ($normalizedName === null) {
            return null;
        }

        $email = $result['email_address'] ?? null;

        if (! is_string($email)) {
            throw new AlmaNoEmailException;
        }

        Log::info('ALMA: Successful login for user: '.$this->jsonEncodeForLog($requestCredentials['uid']));

        return [
            'name' => $normalizedName,
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'is_admin' => false,
            'is_system_user' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $password = $credentials['password'] ?? null;

        if (! is_string($password)) {
            return;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make($password),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return Credentials|null
     */
    private function extractCredentials(array $credentials): ?array
    {
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;

        if (! is_string($username) || ! is_string($password)) {
            return null;
        }

        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @return AuthUserData|null
     */
    private function buildTestAccountUserData(string $accountKey, bool $isAdmin): ?array
    {
        $username = $this->configString("roomz.test-accounts.$accountKey.username");
        $password = $this->configString("roomz.test-accounts.$accountKey.password");
        $email = $this->configString("roomz.test-accounts.$accountKey.email");

        if ($username === null || $password === null || $email === null) {
            return null;
        }

        /** @var string $normalizedName */
        $normalizedName = Utility::normalizeLoginName($username);

        return [
            'name' => $normalizedName,
            'password' => Hash::make($password),
            'email' => $email,
            'is_admin' => $isAdmin,
            'is_system_user' => true,
        ];
    }

    private function configBool(string $key): bool
    {
        $value = config($key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return false;
    }

    private function configFloat(string $key): float
    {
        $value = config($key);

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }

    private function configString(string $key): ?string
    {
        $value = config($key);

        return is_string($value) ? $value : null;
    }

    private function jsonEncodeForLog(mixed $value): string
    {
        $encoded = json_encode($value);

        return is_string($encoded) ? $encoded : 'null';
    }
}
