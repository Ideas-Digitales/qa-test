<?php

namespace App\Cognito\Traits;

/**
 * Trait HasTokens
 * 
 * Este trait proporciona funcionalidad para manejar tokens de autenticación,
 * específicamente para su uso con Amazon Cognito.
 */
trait HasTokens {

    /**
     * El token de acceso proporcionado por el proveedor de autenticación.
     *
     * @var string|null
     */
    protected ?string $accessToken;

    /**
     * El token de ID proporcionado por el proveedor de autenticación.
     *
     * @var string|null
     */
    protected ?string $idToken;

    /**
     * El token de actualización proporcionado por el proveedor de autenticación.
     *
     * @var string|null
     */
    protected ?string $refreshToken;

    /**
     * El tiempo de expiración del token en segundos.
     *
     * @var int|null
     */
    protected ?int $expiresIn;

    /**
     * Establece los tokens de autenticación.
     *
     * @param array $tokens Un array asociativo que contiene los tokens y el tiempo de expiración.
     * @return void
     */
    public function setTokens(array $tokens): void
    {
        $this->accessToken = $tokens['access_token'] ?? null;
        $this->idToken = $tokens['id_token'] ?? null;
        $this->refreshToken = $tokens['refresh_token'] ?? null;
        $this->expiresIn = $tokens['expires_in'] ?? null;
    }

    /**
     * Obtiene el token de acceso.
     *
     * @return string|null El token de acceso o null si no está establecido.
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Obtiene el token de ID.
     *
     * @return string|null El token de ID o null si no está establecido.
     */
    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    /**
     * Obtiene el token de actualización.
     *
     * @return string|null El token de actualización o null si no está establecido.
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Obtiene el tiempo de expiración del token.
     *
     * @return int|null El tiempo de expiración en segundos o null si no está establecido.
     */
    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }
}