<?php

namespace App\Providers;

use App\Cognito\Classes\CognitoAuthorization;
use App\Cognito\Classes\CognitoGuard;
use App\Cognito\Classes\CognitoUserProvider;
use Aws\CognitoIdentity\CognitoIdentityClient;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CognitoIdentityProviderClient::class, function ($app) {
            $parameters = [
                'version' => 'latest',
                'region' => config('services.cognito.region')
            ];

            $cognitoClient = new CognitoIdentityProviderClient($parameters);
            return $cognitoClient;
        });

        $cognitoClient = $this->app->make(CognitoIdentityProviderClient::class);
        $cognitoUserProvider = new CognitoUserProvider($cognitoClient);
        $this->app->instance(UserProvider::class, $cognitoUserProvider);
        $this->app->singleton(CognitoAuthorization::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('cognito', function (Application $app, array $config) {
            $userProvider = $app->make(UserProvider::class);
            return $userProvider;
        });
    
        Auth::extend('cognito_driver', function (Application $app, string $name, array $config) {
            return new CognitoGuard(
                Auth::createUserProvider($config['provider']),
                $app->make(Request::class)
            );
        });
    }
}
 