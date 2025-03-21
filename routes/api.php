<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CognitoController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [CognitoController::class, 'login']);
    Route::post('/register', [CognitoController::class, 'register']);
    Route::post('/refresh-token', [CognitoController::class, 'refreshToken']);
    Route::post('/verify-token', [CognitoController::class, 'verifyToken']);
    Route::post('/check-login-status', [TestController::class, 'checkLoginStatus']);
    Route::post('/get-user-id', [TestController::class, 'getUserId']);
});



// Ruta privada de datos (requiere autenticación)
Route::middleware('auth:api')->get('/datos_priv', [UserController::class, 'datosPrivados']);


Route::prefix('v1/users')->group(function () {
    Route::post('/sendcode', [AuthController::class, 'sendcode'])->middleware(['throttle:3,1']);
    Route::post('/validationcode', [AuthController::class, 'validationcode'])->middleware(['throttle:3,1']);
    Route::post('/createpassword', [AuthController::class, 'createpassword'])->middleware(['throttle:3,1']);
});

Route::prefix('v1/users/public')->group(function () {
    Route::get('/status/{rut}', [AuthController::class, 'status'])->middleware(['throttle:3,1']);
    Route::get('/{rut}', [AuthController::class, 'contracts'])->middleware(['throttle:3,1']);
});
