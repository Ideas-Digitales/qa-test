<?php

namespace App\Services;

interface AuthService
{
    public function isLoggedIn(string $token): bool;
    public function getUserId(string $token): ?string;
}