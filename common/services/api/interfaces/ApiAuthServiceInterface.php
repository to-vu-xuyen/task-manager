<?php

namespace common\services\api\interfaces;

use common\models\user\UserApiToken;

interface ApiAuthServiceInterface
{
    public function authenticate(string $username, string $password): ?array;

    public function refreshToken(string $refreshToken): ?array;


    public function createApiToken(
        int $userId,
        string $name,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): ?UserApiToken;

    public function revokeApiToken(int $tokenId, int $userId): bool;
    public function logout(string $refreshToken): bool;
}