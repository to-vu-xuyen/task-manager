# Error Handling & Rate Limiting

> **Skills:** api-security-best-practices, backend-dev-guidelines

---

## 1. `api/components/ApiErrorHandler.php`

```php
<?php

namespace api\components;

use Yii;
use yii\web\ErrorHandler;
use yii\web\Response;

/**
 * ApiErrorHandler — JSON error responses thay vì HTML
 *
 * - Production: trả message chung, KHÔNG expose stack trace
 * - Development: trả thêm debug info
 */
class ApiErrorHandler extends ErrorHandler
{
    /**
     * @inheritdoc
     */
    protected function renderException($exception)
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;

        // Request ID cho support/debugging correlation
        $requestId = Yii::$app->request->getHeaders()->get('X-Request-Id')
            ?? uniqid('req_', true);

        if ($exception instanceof \yii\web\HttpException) {
            $response->setStatusCode($exception->statusCode);
            $data = [
                'success' => false,
                'error'   => [
                    'code'       => $exception->statusCode,
                    'message'    => $exception->getMessage(),
                    'request_id' => $requestId,
                ],
            ];
        } else {
            $response->setStatusCode(500);
            $data = [
                'success' => false,
                'error'   => [
                    'code'       => 500,
                    'message'    => YII_DEBUG
                        ? $exception->getMessage()
                        : 'Đã xảy ra lỗi hệ thống',
                    'request_id' => $requestId,
                ],
            ];
        }

        // Debug info chỉ hiện khi YII_DEBUG = true
        if (YII_DEBUG) {
            $data['error']['type'] = get_class($exception);
            $data['error']['file'] = $exception->getFile() . ':' . $exception->getLine();
            $data['error']['trace'] = explode("\n", $exception->getTraceAsString());
        }

        $response->data = $data;
        $response->send();
    }
}
```

---

## 2. `api/components/ApiResponse.php`

```php
<?php

namespace api\components;

/**
 * ApiResponse — Static helper cho response formatting
 *
 * Sử dụng khi cần format response ngoài controller
 * (ví dụ: trong middleware hoặc event handler)
 */
final class ApiResponse
{
    /**
     * Envelope pattern: wrap data + meta
     */
    public static function envelope($data, array $meta = []): array
    {
        $result = [
            'success' => true,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $result['meta'] = $meta;
        }

        return $result;
    }

    /**
     * Error envelope
     */
    public static function errorEnvelope(
        string $message,
        int $code = 400,
        array $details = []
    ): array {
        $error = [
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ];

        if (!empty($details)) {
            $error['error']['details'] = $details;
        }

        return $error;
    }
}
```

---

## 3. Rate Limiting — Thay đổi cho `common/models/User.php`

```php
// ═══════════════════════════════════════════════════════════
// THÊM interface implementation vào class User
// User extends ActiveRecord implements IdentityInterface, RateLimitInterface
// ═══════════════════════════════════════════════════════════

use yii\filters\RateLimitInterface;
use yii\web\Request;
use yii\base\Action;

class User extends ActiveRecord implements IdentityInterface, RateLimitInterface
{
    // ... existing code ...

    // ── Rate Limiting (RateLimitInterface) ──

    /**
     * Số request tối đa trong khoảng thời gian
     * @return array [maxRequests, perSeconds]
     */
    public function getRateLimit($request, $action): array
    {
        $params = Yii::$app->params['rateLimit'] ?? [];

        // Auth endpoints: giới hạn chặt hơn
        $controller = $action->controller;
        if ($controller instanceof \api\modules\v1\controllers\AuthController) {
            return $params['auth'] ?? [5, 60];
        }

        return $params['general'] ?? [60, 60];
    }

    /**
     * Load allowance còn lại từ Redis/Cache
     * @return array [allowance, timestamp]
     */
    public function loadAllowance($request, $action): array
    {
        $key = $this->getRateLimitKey($request, $action);

        $cache = Yii::$app->cache;
        $data = $cache->get($key);

        if ($data === false) {
            $rateLimit = $this->getRateLimit($request, $action);
            return [$rateLimit[0], time()];
        }

        return $data;
    }

    /**
     * Lưu allowance còn lại vào Redis/Cache
     */
    public function saveAllowance($request, $action, $allowance, $timestamp): void
    {
        $key = $this->getRateLimitKey($request, $action);
        $rateLimit = $this->getRateLimit($request, $action);

        Yii::$app->cache->set($key, [$allowance, $timestamp], $rateLimit[1]);
    }

    /**
     * Tạo cache key cho rate limiting
     */
    private function getRateLimitKey($request, $action): string
    {
        $controllerId = $action->controller->id;
        $actionId = $action->id;
        return "rate_limit:{$this->id}:{$controllerId}/{$actionId}";
    }
}
```

> **Note:** Cần thêm `use yii\filters\RateLimitInterface;` và implement interface.
> Rate limit data lưu trong cache (FileCache hoặc Redis tùy config).
