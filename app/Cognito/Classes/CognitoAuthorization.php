<?php

namespace App\Cognito\Classes;

use App\Cognito\Classes\CognitoUser;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;
use Aws\VerifiedPermissions\VerifiedPermissionsClient;

class CognitoAuthorization
{
    protected $cognitoClient;

    public function __construct()
    {
        $this->cognitoClient = new CognitoIdentityProviderClient([
            'version' => 'latest',
            'region' => config('services.cognito.region')
        ]);
    }

    public function isLoggedIn(string $token): bool
    {
        try {
            $this->cognitoClient->getUser([
                'AccessToken' => $token,
            ]);
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }
    
    public function getUserId(string $token): ?string
    {
        try {
            $result = $this->cognitoClient->getUser([
                'AccessToken' => $token,
            ]);
            foreach ($result['UserAttributes'] as $attribute) {
                if ($attribute['Name'] === 'sub') {
                    return $attribute['Value'];
                }
            }
            return null;
        } catch (AwsException $e) {
            return null;
        }
    }

    /**
     * Checks if the user is able to perform a specific action.
     *
     * This method implements authorization logic. It can be integrated with
     * an authorization service injected into the CognitoUser constructor,
     * or potentially validate directly with Amazon Verified Permissions
     * (though this is not recommended).
     *
     * @param CognitoUser $user The authenticated user.
     * @param string $actionType The type of action to check authorization for, e.g., 'Read' or 'Write'.
     * @param string $actionId The specific identifier of the action.
     *
     * @return bool Returns true if the user is authorized to perform the action, false otherwise.
     */
    public function authorize(CognitoUser $user, $actionType, $actionId): bool
    {
        // Crear un cliente de Verified Permissions
        $client = new VerifiedPermissionsClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
        ]);

        // Preparar la solicitud a Verified Permissions
        $request = [
            'policyStoreId' => env('VERIFIED_PERMISSIONS_POLICY_STORE_ID'),
            'principal' => [
                'entityType' => 'User',
                'entityId' => $user->getAuthIdentifier(),
                'attributes' => [
                    'isRegisteredUser' => true, // Asegúrate de obtener este valor correctamente
                ],
            ],
            'action' => [
                'actionType' => $actionType,
                'actionId' => $actionId,
            ],
            'resource' => [
                'entityType' => 'Resource',
                'entityId' => 'ConsultarDatos', // TODO
            ],
        ];

        // Verificar los permisos
        $response = $client->isAuthorized($request);

        // Verificar la respuesta
        if ($response['decision'] === 'ALLOW') {
            // El usuario tiene permiso, realizar la acción solicitada
            // ... lógica para consultar o modificar datos ...
            return true;
        } else {
            // El usuario no tiene permiso
            return false;
        }
    }
}