<?php

namespace App\Services;

use Aws\VerifiedPermissions\VerifiedPermissionsClient;
use Aws\Exception\AwsException;

class AwsPermissionChecker implements PermissionChecker
{
    protected $verifiedPermissionsClient;

    public function __construct()
    {
        $this->verifiedPermissionsClient = new VerifiedPermissionsClient([
            'version' => 'latest',
            'region' => config('services.aws.region')
        ]);
    }

    public function isAble(string $userId, string $action, string $resource): bool
    {
        try {
            $result = $this->verifiedPermissionsClient->isAuthorized([
                'PolicyStoreId' => config('services.aws.policy_store_id'),
                'Principal' => [
                    'Type' => 'User',
                    'Id' => $userId,
                ],
                'Action' => $action,
                'Resource' => $resource,
            ]);

            return $result['Authorized'];
        } catch (AwsException $e) {
            // Manejar la excepción según sea necesario
            return false;
        }
    }
}