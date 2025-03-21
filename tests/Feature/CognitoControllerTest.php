<?php

namespace Tests\Feature;

use Tests\TestCase;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;

class CognitoControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $cognitoClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cognitoClient = Mockery::mock(CognitoIdentityProviderClient::class);
        $this->app->instance(CognitoIdentityProviderClient::class, $this->cognitoClient);
    }

    public function test_register_user()
    {
        $this->cognitoClient->shouldReceive('adminCreateUser')
            ->once()
            ->andReturn(['UserConfirmed' => true]);

        $this->cognitoClient->shouldReceive('adminSetUserPassword')
            ->once()
            ->andReturn(true);

        // Ejecutar solicitud POST /api/v1/register
        // Assert HTTP 200
        // Assert json: { status: "success", message: "Registro exitoso" }
    }
}