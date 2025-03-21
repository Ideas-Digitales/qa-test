<?php

namespace App\Services;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;

class CognitoAuthService implements AuthService
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
}