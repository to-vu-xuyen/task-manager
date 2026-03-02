# API Setup & Configuration

> **Skills:** api-patterns, architecture, backend-dev-guidelines

## 1. `.env` — Thêm keys mới

```ini
# JWT (đã có sẵn)
JWT_SECRET=change_this_value

# Sensitive Data Protection (thêm mới)
DATA_ENCRYPTION_KEY=change_this_to_random_32_char_string
DATA_SIGNING_KEY=change_this_to_another_random_string
```

---

## 2. `common/config/bootstrap.php` — Thêm alias `@api`

```diff
 Yii::setAlias('@common', dirname(__DIR__));
 Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
 Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
 Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');
+Yii::setAlias('@api', dirname(dirname(__DIR__)) . '/api');
```

---

## 3. `api/config/bootstrap.php`

```php
<?php
// API-specific bootstrap — intentionally empty
// Có thể thêm alias hoặc event handlers cho API tại đây
```

---

## 4. `api/config/main.php`

```php
<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'api\controllers',

    // ── Versioned Modules ──
    'modules' => [
        'v1' => [
            'class' => 'api\modules\v1\Module',
        ],
        // Tương lai: 'v2' => ['class' => 'api\modules\v2\Module'],
    ],

    'components' => [

        // ── Request: JSON parser, no CSRF ──
        'request' => [
            'baseUrl' => '/task-manager/api',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false,
        ],

        // ── Response: always JSON ──
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],

        // ── User: stateless, no session ──
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],

        // ── URL Rules ──
        // Chi tiết về routing strategies: xem 08-url-routing.md
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                // Auth — explicit routes (custom actions, không theo CRUD)
                'POST v1/auth/login'              => 'v1/auth/login',
                'POST v1/auth/refresh'             => 'v1/auth/refresh',
                'POST v1/auth/logout'              => 'v1/auth/logout',
                'POST v1/auth/tokens'              => 'v1/auth/create-token',
                'DELETE v1/auth/tokens/<id:\d+>'   => 'v1/auth/revoke-token',

                // Tasks — rest\UrlRule (chuẩn CRUD, auto-generate routes)
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/task'],
                    'pluralize' => true,
                ],
            ],
        ],

        // ── Error Handler: JSON errors ──
        'errorHandler' => [
            'class' => 'api\components\ApiErrorHandler',
        ],

        // ── Log ──
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/api/' . date('Y-m-d') . '.log',
                    'maxFileSize' => 10240,
                    'maxLogFiles' => 30,
                ],
            ],
        ],
    ],

    'params' => $params,
];
```

---

## 5. `api/config/params.php`

```php
<?php
return [
    'jwt' => [
        'accessTokenExpire'  => 3600,      // 1 giờ
        'refreshTokenExpire' => 604800,    // 7 ngày
    ],
    'rateLimit' => [
        'general' => [60, 60],   // 60 requests / 60 giây
        'auth'    => [5, 60],    // 5 requests / 60 giây (login/refresh)
    ],
    // CORS — danh sách origins được phép
    // Production: thay bằng domain thực tế
    'cors.allowedOrigins' => [
        'http://localhost',
        'http://localhost:3000',
        // 'https://your-production-domain.com',
    ],
];
```

---

## 6. `api/config/main-local.php` (template)

```php
<?php
return [
    'components' => [
        // Override config cho môi trường local nếu cần
    ],
];
```

## 7. `api/config/params-local.php` (template)

```php
<?php
return [];
```

---

## 8. `api/modules/v1/Module.php`

```php
<?php

namespace api\modules\v1;

/**
 * API v1 Module
 *
 * Mỗi API version là 1 Yii2 sub-module.
 * Khi cần v2, tạo api\modules\v2\Module mà không ảnh hưởng v1 (OCP).
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'api\modules\v1\controllers';
}
```

---

## 9. `api/web/index.php`

```php
<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

(new yii\web\Application($config))->run();
```

---

## 10. `api/web/.htaccess`

```apache
RewriteEngine On

# If a directory or a file exists, use it directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Otherwise forward it to index.php
RewriteRule . index.php
```
