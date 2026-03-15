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

        unset($behaviors['authenticator']);


        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => $this->getAllowedOrigins(),
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Max-Age' => 86400,
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

    protected function getAllowedOrigins(): array
    {
        return Yii::$app->params['cors.allowedOrigins'] ?? [
            'http://localhost',
            'http://localhost:3000',
        ];
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
            'data' => $data,
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
            'error' => [
                'code' => $status,
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
            'data' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => (int) ceil($total / max($perPage, 1)),
            ],
        ];
    }
}