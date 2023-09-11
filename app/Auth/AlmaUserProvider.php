<?php

namespace App\Auth;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
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
        $user_data = $this->getLocalUserInfo($credentials);

        if (!$user_data) {
            $user_data = $this->getRemoteUserInfo([
                'uid' => $credentials['username'],
                'pw' => $credentials['password'],
            ]);
        }

        if (!$user_data) {
            return null;
        }

        $user = User::where('name', $user_data['name'])->first();

        if ($user) {
            $user->update([
                'email' => $user_data['email'],
                'last_login' => Carbon::now(),
            ]);
        } else {
            $user = User::create([
                'name' => $user_data['name'],
                'email' => $user_data['email'],
                'password' => $user_data['password'],
                'is_admin' => $user_data['is_admin'],
                'last_login' => Carbon::now(),
            ]);
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
        if (
            $credentials['username'] == env('ADMIN_USER')
            && $credentials['password'] == env('ADMIN_PASSWORD')
        ) {
            return [
                'name' => env('ADMIN_USER'),
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'email' => env('ADMIN_EMAIL'),
                'is_admin' => true,
            ];
        }

        if (
            $credentials['username'] == env('TEST_USER_01')
            && $credentials['password'] == env('TEST_USER_01_PASSWORD')
        ) {
            return [
                'name' => env('TEST_USER_01'),
                'password' => Hash::make(env('TEST_USER_01_PASSWORD')),
                'email' => env('TEST_USER_01_EMAIL'),
                'is_admin' => false,
            ];
        }

        if (
            $credentials['username'] == env('TEST_USER_02')
            && $credentials['password'] == env('TEST_USER_02_PASSWORD')
        ) {
            return [
                'name' => env('TEST_USER_02'),
                'password' => Hash::make(env('TEST_USER_02_PASSWORD')),
                'email' => env('TEST_USER_02_EMAIL'),
                'is_admin' => false,
            ];
        }

        return null;
    }

    private function getRemoteUserInfo($credentials)
    {
        $response = Curl::to(env('AUTH_API_ENDPOINT'))
            ->withData($credentials)
            ->withTimeout(env('AUTH_API_TIMEOUT'))
            ->withConnectTimeout(env('AUTH_API_TIMEOUT'))
            ->withOption('SSL_VERIFYHOST', 2)
            ->withOption('SSL_VERIFYPEER', 1)
            ->withOption('POST', 1)
            ->withOption('RETURNTRANSFER', true)
            ->enableDebug(storage_path(env('AUTH_API_STORAGE_LOG_FILE')))
            ->post();

        $response = preg_replace('/[\n\r]|\s{2,}/', '', $response);
        $response = XmlToArray::convert($response);

        if ($response['result']['code'] == 0) {
            return [
                'name' => $response['result']['barcode'],
                'email' => $response['result']['email_address'],
                'password' => Hash::make('Test123!'),
                'is_admin' => false,
            ];
        }

        return null;
    }
}
