<?php

namespace App\Cognito\Classes;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use \RuntimeException;
use Illuminate\Support\Facades\Log;

class CognitoUserProvider implements UserProvider {

    private CognitoIdentityProviderClient $cognitoClient;

    protected $clientId;
    protected $clientSecret;
    protected $userPoolId;
    protected $policyStoreId;

    public function __construct(CognitoIdentityProviderClient $cognitoClient) {
        $this->userPoolId = config('services.cognito.user_pool_id');
        $this->clientId = config('services.cognito.client_id');
        $this->clientSecret = config('services.cognito.client_secret');
        $this->cognitoClient = $cognitoClient;
    }
    
    /**
     * Recupera un usuario por su identificador único.
     *
     * @param string $identifier El identificador único del usuario.
     * @return CognitoUser|null
     */
    public function retrieveById($identifier)
    {
        throw new RuntimeException('El método retrieveById() no está implementado.');
    }

    /**
     * Recupera un usuario por su identificador único y token "remember me".
     *
     * @param string $identifier El identificador único del usuario.
     * @param string $token El token "remember me".
     * @return CognitoUser|null
     * TODO: throw y mover a un trait helper
     */
    public function retrieveByToken($identifier, $token)
    {
        throw new RuntimeException('El método retrieveById() no está implementado.');
    }

    /**
     * Actualiza el token "remember me" de un usuario dado.
     *
     * @param Authenticatable $user El usuario.
     * @param string $token El nuevo token "remember me".
     * @return void
     * TODO throw y mover a un trait helper
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        throw new RuntimeException('El método updateRememberToken() no está implementado.');
    }

    /**
     * Recupera un usuario por las credenciales dadas.
     *
     * @param array $credentials Las credenciales del usuario: [login, password].
     * @return CognitoUser|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        try {
            $message = $credentials['login'] ?? '' . $this->clientId;
            $hash = hash_hmac('sha256', $message, $this->clientSecret, true);
            $hash = base64_encode($hash);
            
            $result = $this->cognitoClient->adminInitiateAuth([
                'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
                'ClientId' => env('AWS_COGNITO_CLIENT_ID'),
                'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
                'AuthParameters' => [
                    'USERNAME' => $credentials['login'],
                    'PASSWORD' => $credentials['password'],
                    'SECRET_HASH' => $hash,
                ],
            ]);

            $tokens = [
                'access_token' => $result['AuthenticationResult']['AccessToken'],
                'id_token' => $result['AuthenticationResult']['IdToken'],
                'refresh_token' => $result['AuthenticationResult']['RefreshToken'],
                'expires_in' => $result['AuthenticationResult']['ExpiresIn'],
            ];

            $user = $this->retrieveByAuthToken($tokens['access_token']);
            $user->setTokens($tokens);
            return new $user;
        } catch (AwsException $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
        }

        return null;

    }

    /**
     * Valida las credenciales de un usuario dado.
     *
     * @param Authenticatable $user El usuario.
     * @param array $credentials Las credenciales a validar.
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        \Illuminate\Support\Facades\Log::critical('El método validateCredentials() no está implementado.');
        return true;
    }

    public function retrieveByAuthToken(string $token)
    {
        \Illuminate\Support\Facades\Log::info('CognitoUserProvider::retrieveByAuthToken(), $token: '. $token);
        try {
            $result = $this->cognitoClient->getUser([
                'AccessToken' => $token,
            ]);

            $userId = null;

            foreach ($result['UserAttributes'] as $attribute) {
                if ($attribute['Name'] === 'sub') {
                    $userId = $attribute['Value'];
                    break;
                }
            }

            if ($userId === null) {
                return null;
            }

            return new CognitoUser($userId);
        } catch (AwsException $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
        }

        return null;
    }

    /**
     * Rehash la contraseña del usuario si es necesario.
     *
     * @param Authenticatable $user El usuario.
     * @param array $credentials Las credenciales del usuario.
     * @param bool $forced Si se debe forzar el rehash.
     * @return void
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $forced = false)
    {
        throw new RuntimeException('El método rehashPasswordIfRequired() no está implementado.');
    }
}