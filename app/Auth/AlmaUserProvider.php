<?php

namespace App\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Ixudra\Curl\Facades\Curl;
use Exception;
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

    /**
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
     * @param  array  $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $is_user_found = false;

        // Check local users
        if (!$is_user_found) {
            $user_data = $this->getLocalUserInfo($credentials);
            if ($user_data) {
                $is_user_found = true;
            }
        }

        // Check system users
        if (!$is_user_found) {
            $user_data = $this->getSystemUserInfo($credentials);
            if ($user_data) {
                $is_user_found = true;
            }
        }

        // Check API users
        if (!$is_user_found) {
            $user_data = $this->getRemoteUserInfo($credentials);
            if ($user_data) {
                $is_user_found = true;
            }
        }

        if (!$is_user_found) {
            return null;
        }

        $user = User::where('name', $user_data['name'])->first();

        if ($user) {
            $user->update([
                'email' => $user_data['email'],
                'last_login' => Carbon::now(),
            ]);
        } else {
            try {
                $user = User::create([
                    'name' => $user_data['name'],
                    'email' => $user_data['email'],
                    'password' => $user_data['password'],
                    'is_admin' => $user_data['is_admin'],
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

    /**
     * Validate a user against the given credentials.
     *
     * @param  Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return true;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param Authenticatable $user
     * @param string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed $identifier
     * @param string $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
    }

    private function getLocalUserInfo($credentials)
    {
        if (!config('roomz.test-accounts.is_enabled')) {
            return null;
        }

        if (
            $credentials['username'] == config('roomz.test-accounts.admin.username')
            && $credentials['password'] == config('roomz.test-accounts.admin.password')
        ) {
            return [
                'name' => config('roomz.test-accounts.admin.username'),
                'password' => Hash::make(config('roomz.test-accounts.admin.password')),
                'email' => config('roomz.test-accounts.admin.email'),
                'is_admin' => true,
            ];
        }

        if (
            $credentials['username'] == config('roomz.test-accounts.test1.username')
            && $credentials['password'] == config('roomz.test-accounts.test1.password')
        ) {
            return [
                'name' => config('roomz.test-accounts.test1.username'),
                'password' => Hash::make(config('roomz.test-accounts.test1.password')),
                'email' => config('roomz.test-accounts.test1.email'),
                'is_admin' => false,
            ];
        }

        if (
            $credentials['username'] == config('roomz.test-accounts.test2.username')
            && $credentials['password'] == config('roomz.test-accounts.test2.password')
        ) {
            return [
                'name' => config('roomz.test-accounts.test2.username'),
                'password' => Hash::make(config('roomz.test-accounts.test2.password')),
                'email' => config('roomz.test-accounts.test2.email'),
                'is_admin' => false,
            ];
        }

        return null;
    }

    private function getSystemUserInfo($credentials)
    {
        $user = User::where('name', $credentials['username'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'is_admin' => $user->is_admin,
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

        $response = Curl::to(config('roomz.auth.api.endpoint'))
            ->withData($credentials)
            ->withTimeout(config('roomz.auth.api.timeout'))
            ->withConnectTimeout(config('roomz.auth.api.timeout'))
            ->withOption('SSL_VERIFYHOST', 2)
            ->withOption('SSL_VERIFYPEER', 1)
            ->withOption('POST', 1)
            ->withOption('RETURNTRANSFER', true)
            ->enableDebug(storage_path(config('roomz.auth.api.log_file')))
            ->post();

        if (empty($response)) {
            Log::info(json_encode($credentials['uid']));
            Log::info($response);
        } else {
            $response = preg_replace('/[\n\r]|\s{2,}/', '', $response);
            $response = XmlToArray::convert($response);

            if ($response['result']['code'] == 0) {
                return [
                    'name' => strtolower($credentials['uid']),
                    'email' => $response['result']['email_address'],
                    'password' => Hash::make('Test123!'),
                    'is_admin' => false,
                ];
            }
        }

        return null;
    }
}
