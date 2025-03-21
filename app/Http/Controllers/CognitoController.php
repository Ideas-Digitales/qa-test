<?php

namespace App\Http\Controllers;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Validator;

class CognitoController extends Controller
{
    protected $clientId;
    protected $clientSecret;
    protected $userPoolId;
    protected $policyStoreId;

    /**
     * @var \App\Cognito\Classes\CognitoUserProvider
     */
    protected $userProvider;

    public function __construct(private CognitoIdentityProviderClient $cognitoClient, UserProvider $userProvider)
    {
        $this->userPoolId = config('services.cognito.user_pool_id');
        $this->clientId = config('services.cognito.client_id');
        $this->clientSecret = config('services.cognito.client_secret');
        $this->userProvider = $userProvider;
        //$this->policyStoreId = env('AWS_VERIFIED_PERMISSIONS_POLICY_STORE_ID');
    }

    /**
     * Función para calcular el hash secreto para Cognito
     */
    private function calculateSecretHash($email)
    {
        $message = $email . $this->clientId;
        $hash = hash_hmac('sha256', $message, $this->clientSecret, true);
        return base64_encode($hash);
    }

    /**
     * Endpoint de registro con Amazon Cognito
     */
    public function register(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'email' => 'required|string|email',
            'rut' => 'required|regex:/^\d{7,8}[0-9Kk]$/'
        ]);

        try {
            //$secretHash = base64_encode(hash_hmac('sha256', $request->email . $this->clientId, $this->clientSecret, true));
            $secretHash = $this->calculateSecretHash($request->email);
    
            $result = $this->cognitoClient->adminCreateUser([
                'UserPoolId' => $this->userPoolId,
                'ClientId' => $this->clientId,
                'Username' => $request->rut,
                'TemporaryPassword' => 'Temp321!',
                //'Password' => $request->password,
                'MesaageAction' => 'SUPPRESS',
                'UserAttributes' => [
                    ['Name' => 'email', 'Value' => $request->email], // Requerido por Cognito
                    ['Name' => 'email_verified', 'Value' => 'true'], // Marcamos el email como verificado
                    //['Name' => 'custom:rut', 'Value' => $request->rut] // Atributo personalizado
                ],
                'SecretHash' => $secretHash,
                
            ]);

            $this->cognitoClient->adminSetUserPassword([
                'UserPoolId' => $this->userPoolId,
                'Username' => $request->rut,
                'Password' => $request->password,
                'Permanent' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Registro exitoso',
                'userConfirmed' => $result['UserConfirmed']
            ]);
        } catch (AwsException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getAwsErrorMessage()
            ], 400);
        }
    }

    /**
     * Endpoint de login con Amazon Cognito
     */
    public function login(Request $request)
    {
        // TODO: Validar rut y email con regex
        $request->validate([
            'login' => 'required',//|regex:/^\d{7,8}[0-9Kk]$/',
            'password' => 'required|string'
        ]);

        $user = $this->userProvider->retrieveByCredentials([
            'login' => $request->login,
            'password' => $request->password,
        ]);

        if ($user === null) {
            return response()->json([
               'status' => 'error',
               'message' => 'Unauthorized'
            ], 401);
        }

        $tokens = [
            'access_token' => $user->getAccessToken(),
            'id_token' => $user->getIdToken(),
            'refresh_token' => $user->getRefreshToken(),
            'expires_in' => $user->getExpiresIn(),
        ];

        return response()->json([
            'message' => 'Login exitoso',
            'tokens' => $tokens
        ]);
    }

    /**
     * Endpoint para verificar el token de Amazon Cognito
     */
    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        try {
            $result = $this->cognitoClient->getUser([
                'AccessToken' => $request->token,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Token válido',
                'user' => $result['Username']
            ]);
        } catch (AwsException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getAwsErrorMessage()
            ], 401);
        }
    }

    // Método para refrescar el token cuando expire
    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
            'username' => 'required|string', // Necesitamos el username para calcular SECRET_HASH
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Calcular SECRET_HASH
            $secretHash = $this->calculateSecretHash($request->username);
            
            $result = $this->cognitoClient->adminInitiateAuth([
                'AuthFlow' => 'REFRESH_TOKEN_AUTH',
                'ClientId' => env('AWS_COGNITO_CLIENT_ID'),
                'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
                'AuthParameters' => [
                    'REFRESH_TOKEN' => $request->refresh_token,
                    'SECRET_HASH' => $secretHash,
                ],
            ]);

            $tokens = [
                'access_token' => $result['AuthenticationResult']['AccessToken'],
                'id_token' => $result['AuthenticationResult']['IdToken'],
                'expires_in' => $result['AuthenticationResult']['ExpiresIn'],
            ];

            return response()->json([
                'message' => 'Token actualizado exitosamente',
                'tokens' => $tokens
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}

