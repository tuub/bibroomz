<?php
namespace App\Auth;

//use App\Library\Utility;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
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

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByID($identifier)
    {
        // Get and return a user by their unique identifier
        $this->user = User::find($identifier);
        //dd($identifier);
        //$this->user = AlmaUser::find($identifier);
        return $this->user;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Get and return a user by looking up the given credentials
        if (!$credentials) {
            return null;
        }

        $user = null;
        $userData = null;

        // Is this the configured general admin user? Otherwise, call the configured external auth webservice
        if ($credentials['username'] == getenv('ADMIN_USER') && $credentials['password'] == getenv('ADMIN_PASSWORD')) {
            $userData = [
                'username' => getenv('ADMIN_USER'),
                'password' => Hash::make('Test123!'),
                'email' => getenv('ADMIN_EMAIL'),
            ];
        } else {
            $ws_credentials = [
                'uid' => $credentials['username'],
                'pw' => $credentials['password'],
            ];

            $response = Curl::to(env('AUTH_API_ENDPOINT'))
                ->withData($ws_credentials)
                ->withTimeout(env('AUTH_API_TIMEOUT'))
                ->withConnectTimeout(env('AUTH_API_TIMEOUT'))
                ->withOption('SSL_VERIFYHOST', 0)
                ->withOption('SSL_VERIFYPEER', 0)
                ->withOption('POST', 1)
                ->withOption('RETURNTRANSFER', true)
                ->enableDebug(storage_path(env('AUTH_API_STORAGE_LOG_FILE')))
                ->post();

            if ($response) {
                $response = preg_replace('/[\n\r]|\s{2,}/', '', $response);
                $response = XmlToArray::convert($response);

                /*
                 * CODE 0 = Login OK
                 * CODE 1 = Wrong credentials
                 */

                if ($response['result']['code'] == 0) {
                    $userData = [
                        'name' => $response['result']['primary_id'],
                        'email' => null,
                        'password' => Hash::make('Test123!'),
                    ];
                } else {
                    // FIXME: Message invalid creds and return
                    // $response['result']['messg']
                }
            }

            if ($userData) {
                $user = User::where('name', $userData['name'])->first();
                if ($user) {
                    $user->update([
                        'email' => $userData['email'],
                        'last_login' => Carbon::now(),
                    ]);
                } else {
                    $user = User::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => Hash::make('Test123!'),
                        'last_login' => Carbon::now(),
                    ]);
                }
                $session_data = [
                    'auth_message' => 'Logged in!',
                ];
            } else {
                $session_data = [
                    'auth_message' => 'No response!',
                ];
            }
            session()->put($session_data);
        }

        if ($user) {
            return $user;
        }

        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return true;
        //return $credentials['username'] == $user->barcode || $credentials['username'] == $user->username;
    }

    public function updateRememberToken(Authenticatable $user, $token) { }
    public function retrieveByToken($identifier, $token) { }
}
