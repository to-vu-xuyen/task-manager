<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\filters\auth\CompositeAuth;
use common\services\api\interfaces\ApiAuthServiceInterface;

/**
 * AuthController — Xử lý API authentication endpoints
 *
 * POST /v1/auth/login      → login (không cần auth)
 * POST /v1/auth/refresh     → refresh token (không cần auth)
 * POST /v1/auth/logout      → logout (cần auth)
 * POST /v1/auth/tokens      → tạo API key (cần auth)
 * DELETE /v1/auth/tokens/:id → revoke API key (cần auth)
 */
class AuthController extends BaseApiController
{
    private ApiAuthServiceInterface $authService;

    public function __construct(
        $id,
        $module,
        ApiAuthServiceInterface $authService,
        $config = []
    ) {
        $this->authService = $authService;
        parent::__construct($id, $module, $config);
    }

    /**
     * Login và Refresh KHÔNG cần xác thực
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Tắt auth cho login và refresh
        $behaviors['authenticator']['optional'] = ['login', 'refresh'];

        return $behaviors;
    }

    /**
     * POST /v1/auth/login
     * Body: { "username": "...", "password": "..." }
     */
    public function actionLogin(): array
    {
        $username = Yii::$app->request->getBodyParam('username', '');
        $password = Yii::$app->request->getBodyParam('password', '');

        if (empty($username) || empty($password)) {
            return $this->error('Username và password là bắt buộc', 400);
        }

        $result = $this->authService->authenticate($username, $password);

        if ($result === null) {
            // Không tiết lộ user có tồn tại hay không (security)
            return $this->error('Thông tin đăng nhập không hợp lệ', 401);
        }

        Yii::info("API login: {$username}", 'api.auth');
        return $this->success($result);
    }

    /**
     * POST /v1/auth/refresh
     * Body: { "refresh_token": "rt_..." }
     */
    public function actionRefresh(): array
    {
        $refreshToken = Yii::$app->request->getBodyParam('refresh_token', '');

        if (empty($refreshToken)) {
            return $this->error('Refresh token là bắt buộc', 400);
        }

        $result = $this->authService->refreshToken($refreshToken);

        if ($result === null) {
            return $this->error('Refresh token không hợp lệ hoặc đã hết hạn', 401);
        }

        return $this->success($result);
    }

    /**
     * POST /v1/auth/logout
     * Body: { "refresh_token": "rt_..." }
     */
    public function actionLogout(): array
    {
        $refreshToken = Yii::$app->request->getBodyParam('refresh_token', '');

        if (empty($refreshToken)) {
            return $this->error('Refresh token là bắt buộc', 400);
        }

        $this->authService->logout($refreshToken);

        // Luôn trả success (không tiết lộ token có tồn tại không)
        return $this->success(['message' => 'Đã đăng xuất']);
    }

    /**
     * POST /v1/auth/tokens
     * Body: { "name": "My App", "scopes": ["tasks.read"], "expires_in": 2592000 }
     */
    public function actionCreateToken(): array
    {
        $name = Yii::$app->request->getBodyParam('name', '');
        $scopes = Yii::$app->request->getBodyParam('scopes');
        $expiresIn = Yii::$app->request->getBodyParam('expires_in');

        if (empty($name)) {
            return $this->error('Tên token là bắt buộc', 400);
        }

        $token = $this->authService->createApiToken(
            Yii::$app->user->id,
            $name,
            $scopes,
            $expiresIn ? (int) $expiresIn : null
        );

        if ($token === null) {
            return $this->error('Không thể tạo token', 500);
        }

        Yii::info("API token created: {$name} by user " . Yii::$app->user->id, 'api.auth');

        // CHỈ trả token value lần đầu (sau này không trả lại)
        return $this->success([
            'id' => $token->id,
            'token' => $token->token,
            'name' => $token->name,
            'expires_at' => $token->expires_at,
            'created_at' => $token->created_at,
        ], 201);
    }

    /**
     * DELETE /v1/auth/tokens/:id
     */
    public function actionRevokeToken(int $id): array
    {
        $result = $this->authService->revokeApiToken($id, Yii::$app->user->id);

        if (!$result) {
            return $this->error('Token không tồn tại hoặc không thuộc về bạn', 404);
        }

        Yii::info("API token revoked: #{$id} by user " . Yii::$app->user->id, 'api.auth');
        return $this->success(['message' => 'Token đã bị thu hồi']);
    }
}