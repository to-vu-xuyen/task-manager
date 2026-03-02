# JWT Authentication

> **Skills:** api-security-best-practices, api-patterns (auth.md)
> **Nguyên tắc:** SRP, DIP — JwtHelper là utility thuần, không phụ thuộc framework

---

## 1. `common/components/jwt/JwtHelper.php`

```php
<?php

namespace common\components\jwt;

use Yii;
use RuntimeException;

/**
 * JwtHelper — Pure PHP JWT encode/decode
 *
 * Sử dụng HMAC-SHA256, không cần package bên ngoài.
 * Tuân thủ JWT spec (RFC 7519).
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7519
 */
final class JwtHelper
{
    private const ALGORITHM = 'sha256';
    private const HEADER = ['alg' => 'HS256', 'typ' => 'JWT'];

    /**
     * Tạo JWT token từ payload
     *
     * @param array $payload Data payload (uid, username, iat, exp...)
     * @return string JWT token string (header.payload.signature)
     * @throws RuntimeException Nếu JWT_SECRET chưa cấu hình
     */
    public static function encode(array $payload): string
    {
        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode(self::HEADER, JSON_UNESCAPED_SLASHES));
        $segments[] = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = implode('.', $segments);
        $signature = hash_hmac(self::ALGORITHM, $signingInput, self::getSecret(), true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode và verify JWT token
     *
     * @param string $token JWT token string
     * @return array|null Payload array nếu valid, null nếu invalid/expired
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify signature (timing-safe comparison)
        $signingInput = "{$headerB64}.{$payloadB64}";
        $signature = self::base64UrlDecode($signatureB64);
        $expectedSignature = hash_hmac(self::ALGORITHM, $signingInput, self::getSecret(), true);

        if (!hash_equals($expectedSignature, $signature)) {
            Yii::warning('JWT signature verification failed', 'jwt');
            return null;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($payload)) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            Yii::info('JWT token expired', 'jwt');
            return null;
        }

        // Check "not before" claim
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return null;
        }

        // Check issuer (prevent cross-service token reuse)
        if (isset($payload['iss']) && $payload['iss'] !== 'task-manager') {
            Yii::warning('JWT issuer mismatch', 'jwt');
            return null;
        }

        // Check audience
        if (isset($payload['aud']) && $payload['aud'] !== 'task-manager-api') {
            Yii::warning('JWT audience mismatch', 'jwt');
            return null;
        }

        return $payload;
    }

    /**
     * Kiểm tra nhanh xem string có phải JWT format không
     * JWT luôn có 3 phần ngăn cách bởi dấu chấm
     */
    public static function isJwtFormat(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }

    /**
     * Tạo access token cho user
     */
    public static function createAccessToken(int $userId, string $username): string
    {
        $params = Yii::$app->params['jwt'] ?? [];
        $expire = $params['accessTokenExpire'] ?? 3600;

        return self::encode([
            'uid'      => $userId,
            'username' => $username,
            'iat'      => time(),
            'exp'      => time() + $expire,
            'type'     => 'access',
            'iss'      => 'task-manager',
            'aud'      => 'task-manager-api',
        ]);
    }

    // ── Private helpers ──

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @throws RuntimeException
     */
    private static function getSecret(): string
    {
        // Ưu tiên: .env → params → throw
        $secret = $_ENV['JWT_SECRET']
            ?? Yii::$app->params['jwtSecret']
            ?? null;

        if (empty($secret) || $secret === 'change_this_value') {
            throw new RuntimeException(
                'JWT_SECRET chưa cấu hình hoặc đang dùng giá trị mặc định. '
                . 'Vui lòng set JWT_SECRET trong .env'
            );
        }

        return $secret;
    }
}
```

---

## 2. `common/components/jwt/JwtHttpBearerAuth.php`

```php
<?php

namespace common\components\jwt;

use Yii;
use yii\filters\auth\HttpBearerAuth;

/**
 * JwtHttpBearerAuth — Auth filter cho JWT tokens
 *
 * Extends HttpBearerAuth, chỉ xử lý token có format JWT (3 parts).
 * Nếu token không phải JWT → return null để CompositeAuth thử method tiếp theo.
 *
 * Flow:
 *   Authorization: Bearer eyJhbG... → decode JWT → loginByAccessToken
 *   Authorization: Bearer tk_abc... → return null → CompositeAuth tries next
 */
class JwtHttpBearerAuth extends HttpBearerAuth
{
    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');

        if ($authHeader === null) {
            return null;
        }

        if (!preg_match('/^Bearer\s+(.*?)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];

        // Chỉ xử lý JWT format (3 parts separated by dots)
        // Nếu không phải JWT → return null → CompositeAuth thử Bearer API key
        if (!JwtHelper::isJwtFormat($token)) {
            return null;
        }

        // Verify JWT trước khi gọi loginByAccessToken
        $payload = JwtHelper::decode($token);
        if ($payload === null) {
            // JWT invalid hoặc expired → challenge + fail
            $this->challenge($response);
            $this->handleFailure($response);
            return null; // unreachable, handleFailure throws
        }

        // Login user bằng access token
        // → gọi User::findIdentityByAccessToken($token, JwtHttpBearerAuth::class)
        $identity = $user->loginByAccessToken($token, get_class($this));

        if ($identity === null) {
            $this->challenge($response);
            $this->handleFailure($response);
        }

        return $identity;
    }
}
```

---

## Giải thích: Tại sao tách JwtHelper và JwtHttpBearerAuth?

| Class | Trách nhiệm (SRP) |
|-------|-------------------|
| `JwtHelper` | Encode/decode JWT thuần — **không biết** về HTTP, Yii, request |
| `JwtHttpBearerAuth` | Filter HTTP request — **không biết** cách JWT hoạt động bên trong |

→ Thay đổi JWT algorithm? Sửa `JwtHelper` only.
→ Thay đổi cách extract token từ header? Sửa `JwtHttpBearerAuth` only.
