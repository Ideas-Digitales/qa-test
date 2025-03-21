<?php

namespace App\Cognito\Classes;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CognitoGuard implements Guard
{
    /**
     * @var \App\Cognito\Classes\CognitoUserProvider $userProvider
     */
    protected $provider;
    
    public function __construct(UserProvider $provider, private Request $request)
    {
        $this->provider = $provider;
    }

    private ?Authenticatable $user = null;

    private string $inputKey = 'access_token';

    private string $storageKey = 'access_token';

    /**
     * Determina si el usuario actual está autenticado.
     *
     * @return bool
     */
    public function check()
    {
        \Illuminate\Support\Facades\Log::info('CognitoGuard::check()');
        $user = $this->user();
        \Illuminate\Support\Facades\Log::info('CognitoGuard user: ' . json_encode($user, JSON_PRETTY_PRINT));
        if ($user === null) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario actual es un invitado.
     *
     * @return bool
     */
    public function guest()
    {
        return false;
    }

    /**
     * Determine if the guard has a user instance.
     *
     * This method checks if there is a user currently associated with the guard.
     * It can be used to verify if a user is set without necessarily checking
     * if they are fully authenticated.
     *
     * @return bool Returns true if a user instance is present, false otherwise.
     */
    public function hasUser()
    {
        if ($this->user !== null) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene el usuario actualmente autenticado.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenForRequest();

        if (! empty($token)) {
            $this->user = $this->provider->retrieveByAuthToken($token);
        }

        return $this->user;
    }

    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }
    
    public function id()
    {
        $user = $this->user();
        return $user ? $user->getAuthIdentifier() : null;
    }

    /**
     * Valida las credenciales del usuario.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials[$this->inputKey])) {
            return false;
        }

        $credentials = [$this->storageKey => $credentials[$this->inputKey]];

        if ($this->provider->retrieveByCredentials($credentials)) {
            return true;
        }

        return false;
    }

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function getTokenForRequest()
    {
        $token = $this->request->bearerToken();
        return $token;
    }
}