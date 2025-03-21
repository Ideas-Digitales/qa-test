<?php

namespace App\Http\Controllers;

use Aws\Exception\AwsException;
use Aws\VerifiedPermissions\VerifiedPermissionsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    protected $verifiedPermissionsClient;
    protected $policyStoreId;

    public function __construct()
    {
        $this->verifiedPermissionsClient = new VerifiedPermissionsClient([
            'version' => 'latest',
            'region' => env('AWS_REGION', 'us-east-1'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        
        $this->policyStoreId = env('AWS_VERIFIED_PERMISSIONS_POLICY_STORE_ID');
    }

    /**
     * Endpoint público para obtener datos básicos
     */
    public function datosPublicos()
    {
        // Este endpoint es público y no requiere autenticación
        return response()->json([
            'status' => 'success',
            'data' => [
                'mensaje' => 'Estos son datos públicos',
                'timestamp' => now()->toIso8601String(),
                'info_publica' => [
                    'nombre_servicio' => 'API Demo Laravel con Cognito',
                    'version' => '1.0.0',
                    'documentacion' => 'https://ejemplo.com/docs'
                ]
            ]
        ]);
    }

    /**
     * Endpoint privado para obtener datos protegidos
     */
    public function datosPrivados(Request $request)
    {
        // Obtener el token del encabezado de la solicitud
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token no proporcionado'
            ], 401);
        }

        try {
            // Verificar el token y extraer información del usuario
            $payload = $this->decodeJwtToken($token);
            
            // Verificar permisos con AWS Verified Permissions
            $hasPermission = $this->checkPermission($payload, 'ViewPrivateData');
            
            if (!$hasPermission) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permiso para acceder a estos datos'
                ], 403);
            }

            // Si el usuario tiene permisos, devolver los datos privados
            return response()->json([
                'status' => 'success',
                'data' => [
                    'mensaje' => 'Estos son datos privados',
                    'timestamp' => now()->toIso8601String(),
                    'usuario' => [
                        'id' => $payload['sub'],
                        'email' => $payload['email'] ?? 'No disponible',
                        'nombre' => $payload['name'] ?? 'No disponible'
                    ],
                    'info_confidencial' => [
                        'dato1' => 'Información sensible 1',
                        'dato2' => 'Información sensible 2',
                        'ultimo_acceso' => now()->subDays(rand(1, 10))->toIso8601String()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al verificar el token: ' . $e->getMessage()
            ], 401);
        }
    }

    /**
     * Decodificar el token JWT
     */
    private function decodeJwtToken($token)
    {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) != 3) {
            throw new \Exception('Token inválido');
        }

        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
        return json_decode($payload, true);
    }

    /**
     * Verificar permisos con AWS Verified Permissions
     */
    private function checkPermission($userInfo, $action)
    {
        try {
            // Crear entidades para la consulta de autorización
            $principal = [
                'entityType' => 'User',
                'entityId' => $userInfo['sub']
            ];

            $resource = [
                'entityType' => 'Data',
                'entityId' => 'PrivateData'
            ];

            // Realizar la consulta de autorización a AWS Verified Permissions
            $result = $this->verifiedPermissionsClient->isAuthorized([
                'policyStoreId' => $this->policyStoreId,
                'principal' => $principal,
                'action' => [
                    'actionType' => 'Action',
                    'actionId' => $action
                ],
                'resource' => $resource,
                'context' => [
                    'contextMap' => [
                        'userGroups' => json_encode($userInfo['cognito:groups'] ?? []),
                        'environment' => env('APP_ENV', 'production')
                    ]
                ]
            ]);

            return $result['decision'] === 'ALLOW';
        } catch (AwsException $e) {
            // En caso de error, registrar y denegar el acceso por seguridad
            Log::error('Error al verificar permisos: ' . $e->getMessage());
            return false;
        }
    }
}