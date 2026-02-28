# Bearer Token (API Key) & User Model

> **Skills:** api-security-best-practices, api-patterns
> **Nguyên tắc:** SRP, OCP — ApiToken model chỉ quản lý tokens, User model mở rộng qua interface

---

## 1. Migration: `console/migrations/m260228_000000_create_api_token_table.php`

```php
<?php

use yii\db\Migration;

/**
 * Tạo bảng api_token cho Bearer Token (API Key) và Refresh Token
 */
class m260228_000000_create_api_token_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%api_token}}', [
            'id'           => $this->primaryKey(),
            'user_id'      => $this->integer()->notNull(),
            'token'        => $this->string(500)->notNull(),
            'name'         => $this->string(255)->null()->comment('Tên mô tả, chỉ cho API key'),
            'type'         => $this->string(20)->notNull()->defaultValue('api_key')
                                   ->comment('api_key | refresh_token'),
            'scopes'       => $this->text()->null()->comment('JSON array of scopes'),
            'expires_at'   => $this->dateTime()->null(),
            'last_used_at' => $this->dateTime()->null(),
            'created_at'   => $this->integer()->notNull(),
            'updated_at'   => $this->integer()->notNull(),
        ]);

        // Foreign key
        $this->addForeignKey(
            'fk-api_token-user_id',
            '{{%api_token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Indexes
        $this->createIndex('idx-api_token-token', '{{%api_token}}', 'token', true);
        $this->createIndex('idx-api_token-user_type', '{{%api_token}}', ['user_id', 'type']);
        $this->createIndex('idx-api_token-expires', '{{%api_token}}', 'expires_at');
    }

    public function safeDown()
    {
        $this->dropTable('{{%api_token}}');
    }
}
```

---

## 2. `common/models/ApiToken.php`

```php
<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * ApiToken model — quản lý API Keys và Refresh Tokens
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $token
 * @property string|null $name
 * @property string      $type        (api_key | refresh_token)
 * @property string|null $scopes      JSON array
 * @property string|null $expires_at
 * @property string|null $last_used_at
 * @property int         $created_at
 * @property int         $updated_at
 *
 * @property User $user
 */
class ApiToken extends ActiveRecord
{
    public const TYPE_API_KEY       = 'api_key';
    public const TYPE_REFRESH_TOKEN = 'refresh_token';

    public static function tableName(): string
    {
        return '{{%api_token}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'token', 'type'], 'required'],
            [['user_id'], 'integer'],
            [['token'], 'string', 'max' => 500],
            [['name'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [self::TYPE_API_KEY, self::TYPE_REFRESH_TOKEN]],
            [['scopes'], 'safe'],
            [['expires_at', 'last_used_at'], 'safe'],
            [['token'], 'unique'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    // ── Relations ──

    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    // ── Business Methods ──

    /**
     * Tạo API token mới (cryptographically secure)
     */
    public static function generateToken(
        int $userId,
        string $type = self::TYPE_API_KEY,
        ?string $name = null,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): self {
        $model = new self();
        $model->user_id = $userId;
        $model->token = self::createSecureToken($type);
        $model->type = $type;
        $model->name = $name;
        $model->scopes = $scopes ? json_encode($scopes) : null;
        $model->expires_at = $expiresInSeconds
            ? date('Y-m-d H:i:s', time() + $expiresInSeconds)
            : null;

        return $model;
    }

    /**
     * Token đã hết hạn?
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Không có expiry = vĩnh viễn
        }
        return strtotime($this->expires_at) < time();
    }

    /**
     * Token có scope cần thiết?
     */
    public function hasScope(string $requiredScope): bool
    {
        if ($this->scopes === null) {
            return true; // Không có scopes = full access
        }

        $scopes = json_decode($this->scopes, true);
        return is_array($scopes) && in_array($requiredScope, $scopes, true);
    }

    /**
     * Cập nhật last_used_at
     */
    public function touch(): void
    {
        $this->updateAttributes(['last_used_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Tìm token hợp lệ (chưa hết hạn)
     */
    public static function findValidToken(string $token): ?self
    {
        /** @var self|null $model */
        $model = self::find()
            ->where(['token' => $token])
            ->andWhere([
                'or',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')],
            ])
            ->one();

        return $model;
    }

    // ── Private ──

    private static function createSecureToken(string $type): string
    {
        $prefix = $type === self::TYPE_REFRESH_TOKEN ? 'rt_' : 'tk_';
        return $prefix . Yii::$app->security->generateRandomString(64);
    }
}
```

---

## 3. Thay đổi cho `common/models/User.php`

```php
// ═══════════════════════════════════════════════════════════
// THAY THẾ method findIdentityByAccessToken hiện tại
// Hiện tại: throw new NotSupportedException(...)
// ═══════════════════════════════════════════════════════════

use common\components\jwt\JwtHelper;
use common\components\jwt\JwtHttpBearerAuth;

/**
 * Tìm user identity bằng access token
 *
 * Hỗ trợ 2 loại token:
 * - JWT (type = JwtHttpBearerAuth): decode JWT → lấy uid
 * - API Key (type = HttpBearerAuth): query bảng api_token
 *
 * @param string $token   Token từ Authorization header
 * @param string|null $type  Class name của auth method đang gọi
 * @return static|null
 */
public static function findIdentityByAccessToken($token, $type = null)
{
    // ── JWT Authentication ──
    if ($type === JwtHttpBearerAuth::class) {
        $payload = JwtHelper::decode($token);
        if ($payload === null || !isset($payload['uid'])) {
            return null;
        }
        return static::findIdentity($payload['uid']);
    }

    // ── Bearer Token (API Key) Authentication ──
    $apiToken = ApiToken::findValidToken($token);
    if ($apiToken === null) {
        return null;
    }

    // Cập nhật last_used_at
    $apiToken->touch();

    return static::findIdentity($apiToken->user_id);
}
```

> ⚠️ Cần thêm `use common\models\ApiToken;` vào đầu file User.php

---

## 4. `common/services/api/ApiAuthServiceInterface.php`

```php
<?php

namespace common\services\api;

use common\models\ApiToken;

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
    ): ?ApiToken;

    /**
     * Revoke API key
     */
    public function revokeApiToken(int $tokenId, int $userId): bool;
}
```

---

## 5. `common/services/api/ApiAuthService.php`

```php
<?php

namespace common\services\api;

use Yii;
use common\models\User;
use common\models\ApiToken;
use common\components\jwt\JwtHelper;

/**
 * ApiAuthService — Business logic cho API authentication
 *
 * Xử lý: authenticate, refresh, logout, API key CRUD
 * Không xử lý: HTTP request/response (đó là việc của Controller)
 */
class ApiAuthService implements ApiAuthServiceInterface
{
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
        $refreshToken = ApiToken::generateToken(
            $user->id,
            ApiToken::TYPE_REFRESH_TOKEN,
            null,
            null,
            $refreshExpire
        );

        if (!$refreshToken->save()) {
            Yii::error('Failed to save refresh token: '
                . json_encode($refreshToken->getErrors()), 'api.auth');
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
        $tokenModel = ApiToken::findValidToken($refreshToken);

        if (!$tokenModel || $tokenModel->type !== ApiToken::TYPE_REFRESH_TOKEN) {
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
        $tokenModel = ApiToken::find()
            ->where([
                'token' => $refreshToken,
                'type'  => ApiToken::TYPE_REFRESH_TOKEN,
            ])
            ->one();

        if (!$tokenModel) {
            return false;
        }

        return (bool) $tokenModel->delete();
    }

    /**
     * @inheritdoc
     */
    public function createApiToken(
        int $userId,
        string $name,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): ?ApiToken {
        $token = ApiToken::generateToken(
            $userId,
            ApiToken::TYPE_API_KEY,
            $name,
            $scopes,
            $expiresInSeconds
        );

        if (!$token->save()) {
            Yii::error('Failed to create API token: '
                . json_encode($token->getErrors()), 'api.auth');
            return null;
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function revokeApiToken(int $tokenId, int $userId): bool
    {
        $token = ApiToken::findOne([
            'id'      => $tokenId,
            'user_id' => $userId,
            'type'    => ApiToken::TYPE_API_KEY,
        ]);

        if (!$token) {
            return false;
        }

        return (bool) $token->delete();
    }
}
```

---

## 6. Thêm DI binding vào `common/config/main.php`

```diff
 'container' => [
     'definitions' => [
         // ... existing bindings ...

+        \common\services\api\ApiAuthServiceInterface::class => [
+            'class' => \common\services\api\ApiAuthService::class,
+        ],
     ],
 ],
```
