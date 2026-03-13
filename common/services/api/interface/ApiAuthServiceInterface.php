<?php

namespace common\services\api\interface;

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

    public function validateToken(string $token): ?ApiToken;
    public function revokeApiToken(int $tokenId, int $userId): bool;
    public function logout(string $refreshToken): bool;
}