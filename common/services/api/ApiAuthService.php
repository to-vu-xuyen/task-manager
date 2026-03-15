<?php

namespace common\services\api;

use common\services\user\UserService;
use common\services\user\UserServiceInterface;
use Exception;
use Yii;
use common\models\User;
use common\models\ApiToken;
use common\models\user\UserApiToken;
use common\services\api\interfaces\ApiAuthServiceInterface;
use common\components\jwt\JwtHelper;
use common\repositories\api\ApiTokenRepository;
use common\repositories\api\interfaces\ApiTokenRepositoryInterface;

class ApiAuthService implements ApiAuthServiceInterface
{
    public function __construct(
        private readonly ApiTokenRepositoryInterface $apiTokenRepository,
        private readonly UserServiceInterface $userService,
    ) {
    }

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->userService->findByUsername($username);
        if (!$user || !$user->validatePassword($password)) {
            throw new Exception("Invalid username or password");
        }

        $accessToken = JwtHelper::createAccessToken($user->id, $user->username);

        // Tạo refresh token (lưu DB để có thể revoke)
        $refreshExpire = Yii::$app->params['jwt']['refreshTokenExpire'] ?? 604800;
        $refreshToken = $this->apiTokenRepository->generateToken(
            $user->id,
            UserApiToken::TYPE_REFRESH_TOKEN,
            null,
            null,
            $refreshExpire
        );

        try {
            $this->apiTokenRepository->save($refreshToken);
        } catch (\Throwable $e) {
            Yii::error('Failed to save token: ' . $e->getMessage(), 'api.auth');
            return null;
        }


        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'expires_in' => Yii::$app->params['jwt']['accessTokenExpire'] ?? 3600,
            'token_type' => 'Bearer',
        ];
    }

    public function refreshToken(string $refreshToken): ?array
    {
        $tokenModel = $this->apiTokenRepository->findValidToken($refreshToken);

        if (!$tokenModel || $tokenModel->type !== UserApiToken::TYPE_REFRESH_TOKEN) {
            return null;
        }

        $user = $tokenModel->user;
        if (!$user || $user->status !== User::STATUS_ACTIVE) {
            return null;
        }

        // Tạo access token mới
        $accessToken = JwtHelper::createAccessToken($user->id, $user->username);

        // Cập nhật last_used_at
        $tokenModel->touch();

        return [
            'access_token' => $accessToken,
            'expires_in' => Yii::$app->params['jwt']['accessTokenExpire'] ?? 3600,
            'token_type' => 'Bearer',
        ];
    }

    public function createApiToken(
        int $userId,
        string $name,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): ?UserApiToken {

        $token = $this->apiTokenRepository->generateToken(
            $userId,
            UserApiToken::TYPE_API_KEY,
            $name,
            $scopes,
            $expiresInSeconds
        );

        try {
            $this->apiTokenRepository->save($token);
            return $token;
        } catch (\Throwable $e) {
            Yii::error('Failed to create API token: ' . $e->getMessage(), 'api.auth');
            return null;
        }
    }


    public function revokeApiToken(int $tokenId, int $userId): bool
    {
        $token = $this->apiTokenRepository->findByIdAndUserId(
            $tokenId,
            $userId,
            UserApiToken::TYPE_API_KEY
        );

        if (!$token) {
            return false;
        }

        try {
            $this->apiTokenRepository->delete($token);
            return true;
        } catch (\Throwable $e) {
            Yii::error('Failed to revoke API token: ' . $e->getMessage(), 'api.auth');
            return false;
        }
    }

    public function logout(string $refreshToken): bool
    {
        $tokenModel = $this->apiTokenRepository->findByTokenAndType(
            $refreshToken,
            UserApiToken::TYPE_REFRESH_TOKEN
        );

        if (!$tokenModel) {
            return false;
        }

        try {
            $this->apiTokenRepository->delete($tokenModel);
            return true;
        } catch (\Throwable $e) {
            Yii::error('Failed to delete refresh token: ' . $e->getMessage(), 'api.auth');
            return false;
        }
    }
}