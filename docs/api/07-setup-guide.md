# Setup Guide — API Integration

## Bước 1: Tạo cấu trúc thư mục

```
mkdir api
mkdir api\config
mkdir api\web
mkdir api\modules
mkdir api\modules\v1
mkdir api\modules\v1\controllers
mkdir api\components
mkdir common\components\jwt
mkdir common\components\security
mkdir common\behaviors
mkdir common\services\api
```

## Bước 2: Copy file từ reference code

| Reference File | Copy vào |
|---------------|----------|
| `01-setup-config.md` §3 | `api/config/bootstrap.php` |
| `01-setup-config.md` §4 | `api/config/main.php` |
| `01-setup-config.md` §5 | `api/config/params.php` |
| `01-setup-config.md` §6,7 | `api/config/main-local.php`, `params-local.php` |
| `01-setup-config.md` §8 | `api/modules/v1/Module.php` |
| `01-setup-config.md` §9 | `api/web/index.php` |
| `01-setup-config.md` §10 | `api/web/.htaccess` |
| `02-jwt-authentication.md` §1 | `common/components/jwt/JwtHelper.php` |
| `02-jwt-authentication.md` §2 | `common/components/jwt/JwtHttpBearerAuth.php` |
| `03-bearer-token-auth-service.md` §1 | `console/migrations/m260228_...php` |
| `03-bearer-token-auth-service.md` §2 | `common/models/ApiToken.php` |
| `03-bearer-token-auth-service.md` §3 | Sửa `common/models/User.php` |
| `03-bearer-token-auth-service.md` §4 | `common/services/api/ApiAuthServiceInterface.php` |
| `03-bearer-token-auth-service.md` §5 | `common/services/api/ApiAuthService.php` |
| `03-bearer-token-auth-service.md` §6 | Sửa `common/config/main.php` |
| `04-controllers.md` §1 | `api/modules/v1/controllers/BaseApiController.php` |
| `04-controllers.md` §2 | `api/modules/v1/controllers/AuthController.php` |
| `04-controllers.md` §3 | `api/modules/v1/controllers/TaskController.php` |
| `05-error-handling-rate-limiting.md` §1 | `api/components/ApiErrorHandler.php` |
| `05-error-handling-rate-limiting.md` §2 | `api/components/ApiResponse.php` |
| `05-error-handling-rate-limiting.md` §3 | Sửa `common/models/User.php` |
| `06-sensitive-data-protection.md` §1 | `common/components/security/DataEncryptor.php` |
| `06-sensitive-data-protection.md` §2 | `common/behaviors/EncryptedFieldBehavior.php` |
| `06-sensitive-data-protection.md` §3 | `api/components/DataMasker.php` |
| `06-sensitive-data-protection.md` §4 | `common/components/security/DataSigner.php` |

## Bước 3: Config thay đổi

### `.env` — thêm keys
```ini
DATA_ENCRYPTION_KEY=your_random_32_char_string_here
DATA_SIGNING_KEY=another_random_string_here
```

### `common/config/bootstrap.php` — thêm alias
```php
Yii::setAlias('@api', dirname(dirname(__DIR__)) . '/api');
```

### `common/config/main.php` — thêm DI binding
```php
\common\services\api\ApiAuthServiceInterface::class => [
    'class' => \common\services\api\ApiAuthService::class,
],
```

### `common/models/User.php` — sửa 2 chỗ
1. `findIdentityByAccessToken()` — thay thế `throw NotSupportedException`
2. Implement `RateLimitInterface` — thêm 3 methods

## Bước 4: Migration

```bash
php yii migrate --interactive=0
```

## Bước 5: Apache VirtualHost / .htaccess

Nếu dùng WAMP, đảm bảo `api/web/` accessible qua URL.
Có thể thêm vào Apache config:

```apache
Alias /task-manager/api /path/to/task-manager/api/web
<Directory "/path/to/task-manager/api/web">
    AllowOverride All
    Require all granted
</Directory>
```

## Bước 6: Test

```bash
# Login
curl -X POST http://localhost/task-manager/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Response: { "success": true, "data": { "access_token": "eyJ...", "refresh_token": "rt_...", ... } }

# Protected endpoint (JWT)
curl -X GET http://localhost/task-manager/api/v1/tasks \
  -H "Authorization: Bearer eyJhbGci..."

# Refresh token
curl -X POST http://localhost/task-manager/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "rt_..."}'

# Create API Key
curl -X POST http://localhost/task-manager/api/v1/auth/tokens \
  -H "Authorization: Bearer eyJhbGci..." \
  -H "Content-Type: application/json" \
  -d '{"name": "My App", "expires_in": 2592000}'

# Protected endpoint (API Key)
curl -X GET http://localhost/task-manager/api/v1/tasks \
  -H "Authorization: Bearer tk_abc123..."
```

## SOLID Checklist

| Principle | Đã áp dụng |
|-----------|-----------|
| **S**ingle Responsibility | Controller xử lý HTTP, Service xử lý logic, Repository xử lý DB |
| **O**pen/Closed | Module versioning (v1/v2), CompositeAuth mở rộng auth methods |
| **L**iskov Substitution | ApiAuthServiceInterface → bất kỳ auth service nào implement đều hoạt động |
| **I**nterface Segregation | AuthServiceInterface (session) ≠ ApiAuthServiceInterface (token) |
| **D**ependency Inversion | Controller → ServiceInterface ← Service (DI Container) |
