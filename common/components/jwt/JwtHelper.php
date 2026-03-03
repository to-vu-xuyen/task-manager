<?php
namespace common\components\jwt;

use Yii;
use RuntimeException;

class JwtHelper
{
    private const ALGORITHM = 'sha256';
    private const HEADER = ['alg' => 'HS256', 'typ' => 'JWT'];

    
    public static function encode(array $payload): string{
        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode(self::HEADER, JSON_UNESCAPED_SLASHES));
        $segments[] = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = implode('.', $segments);
        $signature = hash_hmac(self::ALGORITHM, $signingInput, self::getSecret(), true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

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

    public static function isJwtFormat(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }

    public static function createAccessToken(int $userId, string $username, string $issuer = 'task-manager', string $audience = 'task-manager-api'): string
    {
        $params = Yii::$app->params['jwt'] ?? [];
        $expire = $params['accessTokenExpire'] ?? 3600;

        return self::encode([
            'uid'      => $userId,
            'username' => $username,
            'iat'      => time(),
            'exp'      => time() + $expire,
            'type'     => 'access',
            'iss'      => $issuer,
            'aud'      => $audience,
        ]);
    }


    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function getSecret(): string
    {
        // Ưu tiên: .env → params → throw
        $secret = getenv('JWT_SECRET') ?? $_ENV['JWT_SECRET']
            ?? Yii::$app->params['jwtSecret']
            ?? null;

        if (empty($secret) || $secret === 'change_this_value') {
            throw new RuntimeException(
                'JWT_SECRET chưa cấu hình hoặc đang dùng giá trị mặc định. Vui lòng set JWT_SECRET trong .env'
            );
        }

        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET Toooo weeaaak! Mine longer than you. Phải có ít nhất 32 ký tự');
        }

        return $secret;
    }

    
}