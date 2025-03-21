<?php

namespace App\Cognito\Classes;

use App\Cognito\Traits\HasTokens;
use App\Services\CognitoAuthService;
use Illuminate\Contracts\Auth\Authenticatable;
use \RuntimeException;

class CognitoUser implements Authenticatable {

    use HasTokens;

    public function __construct(
        private string $identifier
    ) { }

    private string $rememberToken = '';

    /**
     * Obtiene el nombre del identificador de autenticación.
     *
     * @return string 'sub'
     */
    public function getAuthIdentifierName()
    {
        return 'sub';
    }

    /**
     * Obtiene el identificador único para el usuario.
     * 
     *
     * @return string $identifier ej: c14b5560-0051-707c-c176-56a08868de14 | entityId en Cognito
     */
    public function getAuthIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Obtiene el nombre del campo de contraseña para autenticación.
     *
     * @return string 'password'
     */
    public function getAuthPasswordName()
    {
        return 'password';
    }

    /**
     * Obtiene la contraseña hash para el usuario.
     *
     * @return string $authPassword ej: $2y$10$2e2628926278777506827...
     */
    public function getAuthPassword()
    {
        throw new RuntimeException('No password provided');
    }

    /**
     * Obtiene el token "remember me" del usuario.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return $this->rememberToken;
    }

    /**
     * Establece el token "remember me" del usuario.
     *
     * @param string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->rememberToken = $value;
    }

    /**
     * Obtiene el nombre de la columna del token "remember me".
     *
     * @return string 
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Checks if the user is able to perform a specific action.
     *
     * This method implements authorization logic. It can be integrated with
     * an authorization service injected into the CognitoUser constructor,
     * or potentially validate directly with Amazon Verified Permissions
     * (though this is not recommended).
     *
     * @param string $actionType The type of action to check authorization for.
     * @param string $actionId The specific identifier of the action.
     * @param callable|null $callback Optional callback function for custom authorization logic. ($identifier) => bool
     *
     * @return bool Returns true if the user is authorized to perform the action, false otherwise.
     */
    public function isAble($actionType, $actionId, ?callable $callback = null): bool
    {
        /**
         * Lógica de autorización
         * Opciones:
         * - Integrar con un servicio de autorización del sistema inyectado en el constructor del CognitoUser
         * - Validar directamente con Amazon Verified Permissions (no recomendado) 
         */
        $service = app()->get(CognitoAuthorization::class);
        
        if (!$service->authorize($this, $actionType, $actionId)) {
            return false;
        }

        if ($callback) {
            return boolval($callback($this->getAuthIdentifier()));
        }

        return true;
    }
}