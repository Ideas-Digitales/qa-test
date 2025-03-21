<?php

namespace App\Services;

interface PermissionChecker
{
    public function isAble(string $userId, string $action, string $resource): bool;
}