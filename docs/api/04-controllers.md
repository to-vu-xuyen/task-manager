# API Controllers

> **Skills:** backend-dev-guidelines, api-patterns
> **Nguyên tắc:** SRP — Controller chỉ xử lý HTTP, business logic nằm ở Service

---

## 1. `api/modules/v1/controllers/BaseApiController.php`

```php
<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\RateLimiter;
use common\components\jwt\JwtHttpBearerAuth;

/**
 * BaseApiController — Base cho tất cả API v1 controllers
 *
 * Cung cấp:
 * - CompositeAuth (JWT + Bearer API Key)
 * - CORS
 * - Rate Limiting
 * - Response helpers (success, error, paginated)
 */
abstract class BaseApiController extends Controller
{
    /**
     * @var string|array Serializer config — tắt envelope mặc định của Yii2
     * Vì ta dùng custom envelope qua success()/error()
     */
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Xóa authenticator mặc định, thêm lại sau CORS
        unset($behaviors['authenticator']);

        // ── CORS (phải trước authenticator) ──
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors'  => [
                'Origin'                        => ['*'],
                'Access-Control-Request-Method'  => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age'         => 86400,
            ],
        ];

        // ── Authentication: JWT + Bearer API Key ──
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [
                JwtHttpBearerAuth::class,  // Thử JWT trước
                HttpBearerAuth::class,     // Fallback: API Key
            ],
        ];

        // ── Rate Limiting ──
        $behaviors['rateLimiter'] = [
            'class' => RateLimiter::class,
            'enableRateLimitHeaders' => true,
        ];

        return $behaviors;
    }

    // ── Response Helpers ──

    /**
     * Response thành công
     *
     * @param mixed $data   Data payload
     * @param int   $status HTTP status code
     * @return array
     */
    protected function success($data = null, int $status = 200): array
    {
        Yii::$app->response->statusCode = $status;
        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    /**
     * Response lỗi
     *
     * @param string $message   Error message
     * @param int    $status    HTTP status code
     * @param array  $errors    Validation errors (optional)
     * @return array
     */
    protected function error(string $message, int $status = 400, array $errors = []): array
    {
        Yii::$app->response->statusCode = $status;
        $response = [
            'success' => false,
            'error'   => [
                'code'    => $status,
                'message' => $message,
            ],
        ];

        if (!empty($errors)) {
            $response['error']['details'] = $errors;
        }

        return $response;
    }

    /**
     * Response có phân trang
     *
     * @param array $items   Data items
     * @param int   $total   Total records
     * @param int   $page    Current page
     * @param int   $perPage Items per page
     * @return array
     */
    protected function paginated(array $items, int $total, int $page, int $perPage): array
    {
        Yii::$app->response->statusCode = 200;
        return [
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int) ceil($total / max($perPage, 1)),
            ],
        ];
    }
}
```

---

## 2. `api/modules/v1/controllers/AuthController.php`

```php
<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\filters\auth\CompositeAuth;
use common\services\api\ApiAuthServiceInterface;

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
            'id'         => $token->id,
            'token'      => $token->token,
            'name'       => $token->name,
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
```

---

## 3. `api/modules/v1/controllers/TaskController.php`

```php
<?php

namespace api\modules\v1\controllers;

use Yii;
use common\services\task\TaskServiceInterface;
use common\forms\task\TaskCreateForm;
use common\forms\task\TaskUpdateForm;

/**
 * TaskController — REST API cho Task CRUD
 *
 * GET    /v1/tasks        → index (list, phân trang)
 * GET    /v1/tasks/:id    → view
 * POST   /v1/tasks        → create
 * PUT    /v1/tasks/:id    → update
 * DELETE /v1/tasks/:id    → delete (soft delete)
 */
class TaskController extends BaseApiController
{
    private TaskServiceInterface $taskService;

    public function __construct(
        $id,
        $module,
        TaskServiceInterface $taskService,
        $config = []
    ) {
        $this->taskService = $taskService;
        parent::__construct($id, $module, $config);
    }

    /**
     * GET /v1/tasks?page=1&per-page=20
     */
    public function actionIndex(): array
    {
        $params = Yii::$app->request->queryParams;
        $dataProvider = $this->taskService->search($params);

        $models = $dataProvider->getModels();
        $items = array_map([$this, 'formatTask'], $models);

        $pagination = $dataProvider->getPagination();

        return $this->paginated(
            $items,
            $pagination->totalCount,
            $pagination->getPage() + 1,
            $pagination->getPageSize()
        );
    }

    /**
     * GET /v1/tasks/:id
     */
    public function actionView(int $id): array
    {
        $task = $this->taskService->getByIdForUser($id, Yii::$app->user->id);

        if ($task === null) {
            return $this->error('Task không tồn tại', 404);
        }

        return $this->success($this->formatTask($task));
    }

    /**
     * POST /v1/tasks
     * Body: { "title": "...", "description": "...", ... }
     */
    public function actionCreate(): array
    {
        $form = new TaskCreateForm();
        $form->load(Yii::$app->request->getBodyParams(), '');
        $form->user_id = Yii::$app->user->id;

        $task = $this->taskService->create($form);

        if ($task === null) {
            return $this->error('Validation failed', 422, $form->getErrors());
        }

        return $this->success($this->formatTask($task), 201);
    }

    /**
     * PUT /v1/tasks/:id
     * Body: { "title": "...", ... }
     */
    public function actionUpdate(int $id): array
    {
        $existing = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if ($existing === null) {
            return $this->error('Task không tồn tại', 404);
        }

        $form = new TaskUpdateForm();
        $form->load(Yii::$app->request->getBodyParams(), '');

        $task = $this->taskService->update($id, $form);

        if ($task === null) {
            return $this->error('Validation failed', 422, $form->getErrors());
        }

        return $this->success($this->formatTask($task));
    }

    /**
     * DELETE /v1/tasks/:id
     */
    public function actionDelete(int $id): array
    {
        $existing = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if ($existing === null) {
            return $this->error('Task không tồn tại', 404);
        }

        $result = $this->taskService->delete($id);

        if (!$result) {
            return $this->error('Không thể xóa task', 500);
        }

        return $this->success(['message' => 'Task đã bị xóa']);
    }

    // ── Private ──

    /**
     * Format Task model → array cho API response
     * Tránh expose toàn bộ attributes (security)
     */
    private function formatTask($task): array
    {
        return [
            'id'          => $task->id,
            'title'       => $task->title,
            'description' => $task->description,
            'content'     => $task->content,
            'status'      => $task->status,
            'user_id'     => $task->user_id,
            'assignee_id' => $task->assignee_id,
            'due_at'      => $task->due_at,
            'is_overdue'  => $task->isOverdue(),
            'created_at'  => $task->created_at,
            'updated_at'  => $task->updated_at,
        ];
    }
}
```
