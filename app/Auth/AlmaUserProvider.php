<?php

namespace App\Auth;

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

class AlmaUserProvider implements UserProvider
{
    public $user;

    private Hasher $hasher;

    /**
     * @return void
     */
    public function __construct(User $user, Hasher $hasher)
    {
        $this->user = $user;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return Authenticatable|null
     */
    public function retrieveByID($identifier)
    {
        return User::find($identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $existingUser = $this->findUserByLoginName($credentials['username']);

        if ($existingUser?->isSystemUser()) {
            $userData = $this->getLocalUserInfo($credentials)
                ?? $this->getSystemUserInfo($credentials);

            if (! $userData) {
                return null;
            }

            return $this->upsertAuthenticatedUser($userData, $existingUser);
        }

        $userData = $this->getLocalUserInfo($credentials);

        if ($userData) {
            return $this->upsertAuthenticatedUser($userData, $existingUser);
        }

        $userData = $this->getRemoteUserInfo($credentials);

        if (! $userData) {
            return null;
        }

        return $this->upsertAuthenticatedUser($userData, $existingUser);
    }

    private function upsertAuthenticatedUser(array $userData, ?User $user): ?User
    {
        if ($user) {
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
            } catch (Exception $exc) {
                Log::error($exc);

                return null;
            }
        }

        $session_data = [
            'auth_message' => 'Logged in!',
        ];

        session()->put($session_data);

        return $user;
    }

    private function findUserByLoginName(string $loginName): ?User
    {
        return User::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(Utility::normalizeLoginName($loginName))])
            ->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return true;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
    }

    private function getLocalUserInfo($credentials)
    {
        if (! config('roomz.test-accounts.is_enabled')) {
            return null;
        }

        if (
            $credentials['username'] == config('roomz.test-accounts.admin.username')
            && $credentials['password'] == config('roomz.test-accounts.admin.password')
        ) {
            return [
                'name' => Utility::normalizeLoginName(config('roomz.test-accounts.admin.username')),
                'password' => Hash::make(config('roomz.test-accounts.admin.password')),
                'email' => config('roomz.test-accounts.admin.email'),
                'is_admin' => true,
                'is_system_user' => true,
            ];
        }

        if (
            $credentials['username'] == config('roomz.test-accounts.test1.username')
            && $credentials['password'] == config('roomz.test-accounts.test1.password')
        ) {
            return [
                'name' => Utility::normalizeLoginName(config('roomz.test-accounts.test1.username')),
                'password' => Hash::make(config('roomz.test-accounts.test1.password')),
                'email' => config('roomz.test-accounts.test1.email'),
                'is_admin' => false,
                'is_system_user' => true,
            ];
        }

        if (
            $credentials['username'] == config('roomz.test-accounts.test2.username')
            && $credentials['password'] == config('roomz.test-accounts.test2.password')
        ) {
            return [
                'name' => Utility::normalizeLoginName(config('roomz.test-accounts.test2.username')),
                'password' => Hash::make(config('roomz.test-accounts.test2.password')),
                'email' => config('roomz.test-accounts.test2.email'),
                'is_admin' => false,
                'is_system_user' => true,
            ];
        }

        return null;
    }

    private function getSystemUserInfo($credentials)
    {
        $user = $this->findUserByLoginName($credentials['username']);

        if ($user && $user->isSystemUser() && Hash::check($credentials['password'], $user->password)) {
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

    private function getRemoteUserInfo($credentials)
    {
        $credentials = [
            'uid' => $credentials['username'],
            'pw' => $credentials['password'],
        ];

        $curl = Curl::to(config('roomz.auth.api.endpoint'))
            ->withData($credentials)
            ->withTimeout(config('roomz.auth.api.timeout'))
            ->withConnectTimeout(config('roomz.auth.api.timeout'))
            ->withOption('SSL_VERIFYHOST', 2)
            ->withOption('SSL_VERIFYPEER', 1)
            ->withOption('POST', 1)
            ->withOption('RETURNTRANSFER', true);

        if (config('roomz.auth.api.is_debug')) {
            $curl->enableDebug(storage_path(config('roomz.auth.api.log_file')));
        }

        $response = $curl->post();

        if (empty($response) || ! str_starts_with($response, '<result')) {
            Log::info('ALMA: failed call to API for user: ' . json_encode($credentials['uid']));
            Log::info($response);
        } else {
            $response = preg_replace('/[\n\r]|\s{2,}/', '', $response);
            $response = XmlToArray::convert($response);

            if ($response['result']['code'] == 0) {
                Log::info('ALMA: Successful login for user: ' . json_encode($credentials['uid']));

                return [
                    'name' => Utility::normalizeLoginName($credentials['uid']),
                    'email' => $response['result']['email_address'],
                    'password' => Hash::make(Str::random(64)),
                    'is_admin' => false,
                    'is_system_user' => false,
                ];
            } else {
                Log::info('ALMA: Wrong username/password for user: ' . json_encode($credentials['uid']));
            }
        }

        return null;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make($credentials['password']),
        ])->save();
    }
}
