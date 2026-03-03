# Bearer Token Auth Service — Refactored với Repository Pattern

> **Skills sử dụng:** `api-security-best-practices`, `api-patterns`
> **Nguyên tắc:** SRP, DIP, OCP, ISP — Tách biệt Data Access (Repository) khỏi Business Logic (Service)

---

## Tại sao Doc cũ (`03-bearer-token-auth-service.md`) không dùng Repository?

### Nguyên nhân gốc rễ

Doc `03` được viết theo hướng **"quick reference"** — tập trung vào việc giải thích luồng hoạt động Auth nhanh nhất có thể. Nó tham chiếu đúng kiến trúc Yii2 mặc định: **Model chứa cả data access + business logic** (Active Record Pattern).

Cách viết đó **không sai** nếu dự án đơn giản. Nhưng dự án `task-manager` đã **chủ động lựa chọn** kiến trúc phân lớp:

```
Controller → Service → Repository → Model (ActiveRecord)
```

Bằng chứng:

| Module | Service | Repository | Interface |
|---|---|---|---|
| Task | `TaskService` | `TaskRepository` | `TaskRepositoryInterface` |
| TaskAttachment | `TaskAttachmentService` | `TaskAttachmentRepository` | `TaskAttachmentRepositoryInterface` |
| ActivityLog | `ActivityLogService` | `ActivityLogRepository` + `CachedActivityLogRepository` | `ActivityLogRepositoryInterface` |
| **API Auth (Doc cũ)** | `ApiAuthService` | ❌ **THIẾU** | ❌ **THIẾU** |

### 5 vi phạm SOLID cụ thể trong Doc cũ

1. **SRP:** `ApiAuthService` vừa xử lý business logic, vừa gọi trực tiếp `ApiToken::find()`, `::findOne()`, `->save()`, `->delete()`
2. **DIP:** Phụ thuộc trực tiếp vào concrete class `ApiToken` (ActiveRecord), không qua abstraction
3. **Consistency:** Phá vỡ pattern đã thiết lập ở Task, TaskAttachment, ActivityLog
4. **Testability:** Không thể mock data layer khi unit test
5. **OCP:** Nếu muốn đổi storage (SQL → Redis), phải sửa trực tiếp `ApiAuthService`

---

## Cấu trúc file mới (Refactored)

```
common/
├── models/
│   └── user/
│       └── UserApiToken.php                          ← Model (ActiveRecord, giữ nguyên)
├── repositories/
│   └── apitoken/
│       ├── interfaces/
│       │   └── ApiTokenRepositoryInterface.php       ← [NEW] Contract
│       └── ApiTokenRepository.php                    ← [NEW] SQL Implementation
└── services/
    └── api/
        ├── interface/
        │   └── ApiAuthServiceInterface.php           ← [SỬA] Chuẩn hóa
        └── ApiAuthService.php                        ← [SỬA] Inject Repository
```

---

## 1. `common/repositories/apitoken/interfaces/ApiTokenRepositoryInterface.php` [NEW]

```php
<?php

namespace common\repositories\apitoken\interfaces;

use common\models\user\UserApiToken;

/**
 * ApiTokenRepositoryInterface — Contract cho API Token data access
 *
 * Tuân thủ DIP: Service phụ thuộc abstraction này,
 * không phụ thuộc trực tiếp vào ActiveRecord
 */
interface ApiTokenRepositoryInterface
{
    /**
     * Tìm token hợp lệ (chưa hết hạn) theo chuỗi token
     */
    public function findValidToken(string $token): ?UserApiToken;

    /**
     * Tìm token theo chuỗi token và type (không check expiry)
     */
    public function findByTokenAndType(string $token, string $type): ?UserApiToken;

    /**
     * Tìm token theo ID và user_id (ownership check)
     */
    public function findByIdAndUserId(int $id, int $userId, ?string $type = null): ?UserApiToken;

    /**
     * Lấy tất cả tokens của user theo type
     */
    public function findAllByUserId(int $userId, ?string $type = null): array;

    /**
     * Lưu token (create hoặc update)
     *
     * @throws \RuntimeException nếu save fail
     */
    public function save(UserApiToken $token): void;

    /**
     * Xóa token
     *
     * @throws \RuntimeException nếu delete fail
     */
    public function delete(UserApiToken $token): void;

    /**
     * Xóa tất cả tokens hết hạn (cleanup job)
     *
     * @return int số tokens đã xóa
     */
    public function deleteExpired(): int;
}
```

---

## 2. `common/repositories/apitoken/ApiTokenRepository.php` [NEW]

```php
<?php

namespace common\repositories\apitoken;

use Yii;
use common\models\user\UserApiToken;
use common\repositories\apitoken\interfaces\ApiTokenRepositoryInterface;

/**
 * ApiTokenRepository — SQL implementation
 *
 * Tất cả data access operations cho UserApiToken được tập trung ở đây.
 * Service layer KHÔNG BAO GIỜ gọi trực tiếp ActiveRecord queries.
 */
class ApiTokenRepository implements ApiTokenRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function findValidToken(string $token): ?UserApiToken
    {
        return UserApiToken::find()
            ->where(['token' => $token])
            ->andWhere([
                'or',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')],
            ])
            ->one();
    }

    /**
     * @inheritdoc
     */
    public function findByTokenAndType(string $token, string $type): ?UserApiToken
    {
        return UserApiToken::find()
            ->where([
                'token' => $token,
                'type'  => $type,
            ])
            ->one();
    }

    /**
     * @inheritdoc
     */
    public function findByIdAndUserId(int $id, int $userId, ?string $type = null): ?UserApiToken
    {
        $query = UserApiToken::find()
            ->where([
                'id'      => $id,
                'user_id' => $userId,
            ]);

        if ($type !== null) {
            $query->andWhere(['type' => $type]);
        }

        return $query->one();
    }

    /**
     * @inheritdoc
     */
    public function findAllByUserId(int $userId, ?string $type = null): array
    {
        $query = UserApiToken::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($type !== null) {
            $query->andWhere(['type' => $type]);
        }

        return $query->all();
    }

    /**
     * @inheritdoc
     */
    public function save(UserApiToken $token): void
    {
        if (!$token->validate()) {
            throw new \DomainException(
                'Token validation failed: ' . json_encode($token->getErrors())
            );
        }

        if (!$token->save(false)) {
            throw new \RuntimeException('Cannot save UserApiToken');
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(UserApiToken $token): void
    {
        if ($token->isNewRecord) {
            throw new \DomainException('Cannot delete unsaved token');
        }

        if (!$token->delete()) {
            throw new \RuntimeException('Cannot delete UserApiToken');
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteExpired(): int
    {
        return UserApiToken::deleteAll([
            'and',
            ['is not', 'expires_at', null],
            ['<', 'expires_at', date('Y-m-d H:i:s')],
        ]);
    }
}
```

---

## 3. `common/services/api/interface/ApiAuthServiceInterface.php` [SỬA]

```php
<?php

namespace common\services\api\interface;

use common\models\user\UserApiToken;

/**
 * Contract cho API Authentication operations
 *
 * Tách riêng khỏi AuthServiceInterface (ISP):
 * - AuthServiceInterface: session-based (frontend)
 * - ApiAuthServiceInterface: token-based (API)
 */
interface ApiAuthServiceInterface
{
    /**
     * Xác thực credentials và trả về token pair
     * @return array|null {access_token, refresh_token, expires_in, token_type}
     */
    public function authenticate(string $username, string $password): ?array;

    /**
     * Refresh access token bằng refresh token
     * @return array|null {access_token, expires_in, token_type}
     */
    public function refreshToken(string $refreshToken): ?array;

    /**
     * Invalidate refresh token (logout)
     */
    public function logout(string $refreshToken): bool;

    /**
     * Tạo API key cho user
     */
    public function createApiToken(
        int $userId,
        string $name,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): ?UserApiToken;

    /**
     * Revoke API key
     */
    public function revokeApiToken(int $tokenId, int $userId): bool;
}
```

---

## 4. `common/services/api/ApiAuthService.php` [SỬA — TRỌNG TÂM]

### So sánh Before / After

| Đặc điểm | Doc cũ (Before) | Doc mới (After) |
|---|---|---|
| Data access | Gọi trực tiếp `ApiToken::find()`, `::findOne()`, `->save()` | Ủy thác cho `$this->tokenRepository` |
| Dependency | Concrete `ApiToken` class | `ApiTokenRepositoryInterface` (abstraction) |
| Constructor | Không có | Inject `ApiTokenRepositoryInterface` |
| Testability | Không mock được DB | Mock `ApiTokenRepositoryInterface` dễ dàng |
| Consistency | Khác pattern với Task, ActivityLog | Cùng pattern |

### Code đã refactor

```php
<?php

namespace common\services\api;

use Yii;
use common\models\User;
use common\models\user\UserApiToken;
use common\components\jwt\JwtHelper;
use common\repositories\apitoken\ApiTokenRepository;
use common\repositories\apitoken\interfaces\ApiTokenRepositoryInterface;

/**
 * ApiAuthService — Business logic cho API authentication
 *
 * Xử lý: authenticate, refresh, logout, API key CRUD
 * Không xử lý:
 * - HTTP request/response (đó là việc của Controller)
 * - Database queries (đó là việc của Repository)
 */
class ApiAuthService implements ApiAuthServiceInterface
{
    private ApiTokenRepositoryInterface $tokenRepository;

    public function __construct(?ApiTokenRepositoryInterface $tokenRepository = null)
    {
        $this->tokenRepository = $tokenRepository ?? new ApiTokenRepository();
    }

    /**
     * @inheritdoc
     */
    public function authenticate(string $username, string $password): ?array
    {
        $user = User::findByUsername($username);

        if (!$user || !$user->validatePassword($password)) {
            return null;
        }

        // Tạo JWT access token
        $accessToken = JwtHelper::createAccessToken($user->id, $user->username);

        // Tạo refresh token (lưu DB để có thể revoke)
        $refreshExpire = Yii::$app->params['jwt']['refreshTokenExpire'] ?? 604800;
        $refreshToken = UserApiToken::generateToken(
            $user->id,
            UserApiToken::TYPE_REFRESH_TOKEN,
            null,
            null,
            $refreshExpire
        );

        try {
            $this->tokenRepository->save($refreshToken);
        } catch (\Throwable $e) {
            Yii::error('Failed to save refresh token: ' . $e->getMessage(), 'api.auth');
            return null;
        }

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken->token,
            'expires_in'    => Yii::$app->params['jwt']['accessTokenExpire'] ?? 3600,
            'token_type'    => 'Bearer',
        ];
    }

    /**
     * @inheritdoc
     */
    public function refreshToken(string $refreshToken): ?array
    {
        $tokenModel = $this->tokenRepository->findValidToken($refreshToken);

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
            'expires_in'   => Yii::$app->params['jwt']['accessTokenExpire'] ?? 3600,
            'token_type'   => 'Bearer',
        ];
    }

    /**
     * @inheritdoc
     */
    public function logout(string $refreshToken): bool
    {
        $tokenModel = $this->tokenRepository->findByTokenAndType(
            $refreshToken,
            UserApiToken::TYPE_REFRESH_TOKEN
        );

        if (!$tokenModel) {
            return false;
        }

        try {
            $this->tokenRepository->delete($tokenModel);
            return true;
        } catch (\Throwable $e) {
            Yii::error('Failed to delete refresh token: ' . $e->getMessage(), 'api.auth');
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function createApiToken(
        int $userId,
        string $name,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): ?UserApiToken {
        $token = UserApiToken::generateToken(
            $userId,
            UserApiToken::TYPE_API_KEY,
            $name,
            $scopes,
            $expiresInSeconds
        );

        try {
            $this->tokenRepository->save($token);
            return $token;
        } catch (\Throwable $e) {
            Yii::error('Failed to create API token: ' . $e->getMessage(), 'api.auth');
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function revokeApiToken(int $tokenId, int $userId): bool
    {
        $token = $this->tokenRepository->findByIdAndUserId(
            $tokenId,
            $userId,
            UserApiToken::TYPE_API_KEY
        );

        if (!$token) {
            return false;
        }

        try {
            $this->tokenRepository->delete($token);
            return true;
        } catch (\Throwable $e) {
            Yii::error('Failed to revoke API token: ' . $e->getMessage(), 'api.auth');
            return false;
        }
    }
}
```

---

## 5. Thêm DI binding vào `common/config/main.php`

```diff
 'container' => [
     'definitions' => [
         // ... existing bindings ...

+        \common\repositories\apitoken\interfaces\ApiTokenRepositoryInterface::class => [
+            'class' => \common\repositories\apitoken\ApiTokenRepository::class,
+        ],
+        \common\services\api\interface\ApiAuthServiceInterface::class => [
+            'class' => \common\services\api\ApiAuthService::class,
+        ],
     ],
 ],
```

---

## 6. Lưu ý về `User::findIdentityByAccessToken`

Method `findIdentityByAccessToken` trong `User.php` hiện đang gọi trực tiếp `ApiToken::findValidToken()`. Đây là một **ngoại lệ chấp nhận được** vì:

1. `findIdentityByAccessToken` là method **bắt buộc** của Yii2 `IdentityInterface` — nó là **static method**, không thể inject dependency qua constructor
2. Nó thuộc về tầng **Framework Integration** (Yii2 authentication system), không phải business logic
3. Các framework khác (Laravel, Symfony) cũng có cùng pattern: Identity resolver gọi trực tiếp model

**Tuy nhiên**, cần đảm bảo class reference chính xác. Hiện tại `User.php` đang dùng `ApiToken` nhưng model thực tế đã đổi thành `UserApiToken`:

```diff
- use common\models\ApiToken;
+ use common\models\user\UserApiToken;

  public static function findIdentityByAccessToken($token, $type = null)
  {
      // ── JWT Authentication ──
      if ($type === JwtHttpBearerAuth::class) {
          // ... (giữ nguyên)
      }

      // ── Bearer Token (API Key) Authentication ──
-     $apiToken = ApiToken::findValidToken($token);
+     $apiToken = UserApiToken::findValidToken($token);
      // ... (giữ nguyên)
  }
```

---

## Tổng kết những thay đổi so với Doc cũ

| # | Thay đổi | Lý do |
|---|---|---|
| 1 | Thêm `ApiTokenRepositoryInterface` | DIP — Service phụ thuộc abstraction |
| 2 | Thêm `ApiTokenRepository` | SRP — Tập trung data access vào 1 nơi |
| 3 | `ApiAuthService` inject Repository qua constructor | Consistent với `TaskService`, `ActivityLogService` |
| 4 | Service dùng `$this->tokenRepository->save()` thay vì `$token->save()` | Không gọi trực tiếp ActiveRecord |
| 5 | Error handling dùng `try/catch` thay vì `if (!$token->save())` | Consistent với `TaskRepository.save()` pattern |
| 6 | Thêm `deleteExpired()` vào Repository | Utility method cho cleanup cron job |
| 7 | Đổi `ApiToken` → `UserApiToken` | Khớp với model thực tế user đã tạo |
