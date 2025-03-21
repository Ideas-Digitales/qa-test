## Instalación como submódulo
```
git submodule add -b main https://github.com/SkySurCL/sutyp-cognito-
guard app/Cognito
```

## Instalacion AWS SDK en Laravel
```
composer require aws/aws-sdk-php-laravel
```

## Provider configuration

**config/auth.php**
```
return [
    // ...
    'guards' => [
        'cognito_guard' => [
            'driver' => 'cognito_driver',
            'provider' => 'users',
        ],
    ],
    // ...
    'providers' => [
        'users' => [
            'driver' => 'cognito',
        ],
    ],
    // ...
]
```

**app/Providers/AppServiceProvider.php**
```
use App\Cognito\Classes\CognitoAuthorization;
use App\Cognito\Classes\CognitoUserProvider;
use Illuminate\Contracts\Foundation\Application;
use App\Cognito\Classes\CognitoGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// ...
public function register(): void
{
    $this->app->singleton(CognitoAuthorization::class);
}
// ...
public function boot(): void
{
    Auth::provider('cognito', function (Application $app, array $config) {
        return new CognitoUserProvider();
    });

    Auth::extend('cognito_driver', function (Application $app, string $name, array $config) {
        return new CognitoGuard(
            Auth::createUserProvider($config['provider']),
            $app->make(Request::class)
        );
    });
}
```
