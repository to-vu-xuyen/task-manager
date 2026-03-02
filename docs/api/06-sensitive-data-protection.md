# Sensitive Data Protection

> **Skills:** api-security-best-practices
> **Nguyên tắc:** SRP — mỗi class 1 trách nhiệm; DIP — dùng Yii2 Security component

---

## 1. `common/components/security/DataEncryptor.php`

```php
<?php

namespace common\components\security;

use Yii;
use RuntimeException;

/**
 * DataEncryptor — Mã hóa/giải mã dữ liệu nhạy cảm
 *
 * Wrapper quanh Yii2 Security component:
 * - encryptByKey: AES-256 + authenticated encryption (HMAC tự động)
 * - Key derivation: HKDF (an toàn cho server-side)
 *
 * Dữ liệu đã encrypt sẽ KHÔNG đọc được nếu không có key.
 * Nếu bị tamper → decrypt trả về false (MAC verification failed).
 */
final class DataEncryptor
{
    /**
     * Prefix marker cho encrypted data — dùng để phát hiện data đã encrypt
     * Tránh double-encrypt và false positive detection
     */
    public const ENCRYPTED_PREFIX = 'enc:';

    /**
     * Mã hóa data
     *
     * @param string $plainText   Data cần mã hóa
     * @param string|null $key    Custom key (mặc định: DATA_ENCRYPTION_KEY)
     * @return string             Prefixed base64-encoded ciphertext
     * @throws RuntimeException   Nếu key chưa cấu hình
     */
    public static function encrypt(string $plainText, ?string $key = null): string
    {
        $key = $key ?? self::getKey();
        $encrypted = Yii::$app->security->encryptByKey($plainText, $key);

        // Prefix + Base64 encode để lưu DB (binary-safe)
        return self::ENCRYPTED_PREFIX . base64_encode($encrypted);
    }

    /**
     * Giải mã data
     *
     * @param string $cipherText  Prefixed base64-encoded ciphertext
     * @param string|null $key    Custom key (mặc định: DATA_ENCRYPTION_KEY)
     * @return string|null        Plaintext, hoặc null nếu tampered/invalid
     */
    public static function decrypt(string $cipherText, ?string $key = null): ?string
    {
        $key = $key ?? self::getKey();

        // Strip prefix nếu có
        if (str_starts_with($cipherText, self::ENCRYPTED_PREFIX)) {
            $cipherText = substr($cipherText, strlen(self::ENCRYPTED_PREFIX));
        }

        $decoded = base64_decode($cipherText, true);

        if ($decoded === false) {
            return null;
        }

        $decrypted = Yii::$app->security->decryptByKey($decoded, $key);

        // decryptByKey trả false nếu MAC verification failed (bị tamper)
        return $decrypted === false ? null : $decrypted;
    }

    /**
     * Kiểm tra xem value đã được encrypt chưa
     * Sử dụng prefix marker — chính xác 100%, không dựa vào heuristic
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::ENCRYPTED_PREFIX);
    }

    private static function getKey(): string
    {
        $key = $_ENV['DATA_ENCRYPTION_KEY'] ?? null;

        if (empty($key)) {
            throw new RuntimeException(
                'DATA_ENCRYPTION_KEY chưa cấu hình trong .env'
            );
        }

        return $key;
    }
}
```

---

## 2. `common/behaviors/EncryptedFieldBehavior.php`

```php
<?php

namespace common\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use common\components\security\DataEncryptor;

/**
 * EncryptedFieldBehavior — Auto encrypt/decrypt trường nhạy cảm
 *
 * Attach vào ActiveRecord model, tự động:
 * - Encrypt trước khi INSERT/UPDATE
 * - Decrypt sau khi SELECT
 *
 * Usage:
 *   public function behaviors() {
 *       return [
 *           'encrypted' => [
 *               'class' => EncryptedFieldBehavior::class,
 *               'attributes' => ['phone_number', 'id_card'],
 *           ],
 *       ];
 *   }
 */
class EncryptedFieldBehavior extends Behavior
{
    /**
     * @var string[] Danh sách tên thuộc tính cần encrypt
     */
    public array $attributes = [];

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT  => 'encryptAttributes',
            ActiveRecord::EVENT_BEFORE_UPDATE  => 'encryptAttributes',
            ActiveRecord::EVENT_AFTER_FIND     => 'decryptAttributes',
            ActiveRecord::EVENT_AFTER_INSERT   => 'decryptAttributes',
            ActiveRecord::EVENT_AFTER_UPDATE   => 'decryptAttributes',
        ];
    }

    /**
     * Encrypt các trường trước khi lưu DB
     */
    public function encryptAttributes(): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        foreach ($this->attributes as $attribute) {
            $value = $owner->getAttribute($attribute);
            if ($value !== null && $value !== '' && !DataEncryptor::isEncrypted($value)) {
                $owner->setAttribute($attribute, DataEncryptor::encrypt($value));
            }
        }
    }

    /**
     * Decrypt các trường sau khi load từ DB
     */
    public function decryptAttributes(): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        foreach ($this->attributes as $attribute) {
            $value = $owner->getAttribute($attribute);
            if ($value !== null && $value !== '' && DataEncryptor::isEncrypted($value)) {
                $decrypted = DataEncryptor::decrypt($value);
                if ($decrypted !== null) {
                    $owner->setAttribute($attribute, $decrypted);
                }
            }
        }
    }
}
```

---

## 3. `api/components/DataMasker.php`

```php
<?php

namespace api\components;

/**
 * DataMasker — Che giấu dữ liệu nhạy cảm trong API response
 *
 * Dùng khi API consumer không cần full data (hiển thị trên UI).
 * KHÔNG thay thế cho encryption — chỉ dùng cho display.
 */
final class DataMasker
{
    /**
     * Mask số điện thoại: 0901234567 → 090***4567
     */
    public static function phone(string $phone): string
    {
        $len = mb_strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        $show = 3;
        $tail = 4;
        return mb_substr($phone, 0, $show)
            . str_repeat('*', max($len - $show - $tail, 3))
            . mb_substr($phone, -$tail);
    }

    /**
     * Mask email: user@company.com → u***@company.com
     */
    public static function email(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }

        [$local, $domain] = $parts;
        $masked = mb_substr($local, 0, 1) . str_repeat('*', max(mb_strlen($local) - 1, 3));

        return $masked . '@' . $domain;
    }

    /**
     * Mask số thẻ: 4111111111111111 → ****1111
     */
    public static function cardNumber(string $card): string
    {
        $clean = preg_replace('/\D/', '', $card);
        $len = strlen($clean);
        if ($len < 4) {
            return str_repeat('*', $len);
        }
        return str_repeat('*', $len - 4) . substr($clean, -4);
    }

    /**
     * Mask tổng quát: giữ lại prefix và suffix
     *
     * @param string $value   Giá trị cần mask
     * @param int    $prefix  Số ký tự giữ ở đầu
     * @param int    $suffix  Số ký tự giữ ở cuối
     * @param string $char    Ký tự mask
     */
    public static function generic(
        string $value,
        int $prefix = 2,
        int $suffix = 2,
        string $char = '*'
    ): string {
        $len = mb_strlen($value);
        if ($len <= $prefix + $suffix) {
            return str_repeat($char, $len);
        }
        return mb_substr($value, 0, $prefix)
            . str_repeat($char, $len - $prefix - $suffix)
            . mb_substr($value, -$suffix);
    }

    /**
     * Mask nhiều fields trong array (dùng cho API response)
     *
     * @param array $data     Response data
     * @param array $rules    ['field_name' => 'phone|email|card|generic']
     * @return array          Data đã mask
     */
    public static function maskFields(array $data, array $rules): array
    {
        foreach ($rules as $field => $type) {
            if (!isset($data[$field]) || $data[$field] === null) {
                continue;
            }

            $data[$field] = match ($type) {
                'phone' => self::phone($data[$field]),
                'email' => self::email($data[$field]),
                'card'  => self::cardNumber($data[$field]),
                default => self::generic($data[$field]),
            };
        }

        return $data;
    }
}
```

---

## 4. `common/components/security/DataSigner.php`

```php
<?php

namespace common\components\security;

use RuntimeException;

/**
 * DataSigner — HMAC-SHA256 ký và xác thực dữ liệu
 *
 * Đảm bảo data integrity (data không bị sửa đổi).
 * Use cases: webhook payloads, callback URLs, inter-service communication.
 *
 * KHÔNG mã hóa data — chỉ tạo chữ ký để verify.
 */
final class DataSigner
{
    private const ALGORITHM = 'sha256';

    /**
     * Ký data
     *
     * @param string $payload   Data cần ký (có thể JSON string)
     * @param string|null $key  Signing key (mặc định: DATA_SIGNING_KEY)
     * @return string           Signature: "sha256=abc123..."
     */
    public static function sign(string $payload, ?string $key = null): string
    {
        $key = $key ?? self::getKey();
        $hash = hash_hmac(self::ALGORITHM, $payload, $key);

        return self::ALGORITHM . '=' . $hash;
    }

    /**
     * Xác thực signature
     *
     * @param string $payload     Data gốc
     * @param string $signature   Signature cần verify ("sha256=abc123...")
     * @param string|null $key    Signing key
     * @return bool               True nếu signature hợp lệ
     */
    public static function verify(
        string $payload,
        string $signature,
        ?string $key = null
    ): bool {
        $expected = self::sign($payload, $key);

        // Timing-safe comparison — chống timing attack
        return hash_equals($expected, $signature);
    }

    /**
     * Ký array data (tự động JSON encode)
     */
    public static function signArray(array $data, ?string $key = null): string
    {
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return self::sign($payload, $key);
    }

    /**
     * Verify array data
     */
    public static function verifyArray(
        array $data,
        string $signature,
        ?string $key = null
    ): bool {
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return self::verify($payload, $signature, $key);
    }

    private static function getKey(): string
    {
        $key = $_ENV['DATA_SIGNING_KEY'] ?? null;

        if (empty($key)) {
            throw new RuntimeException(
                'DATA_SIGNING_KEY chưa cấu hình trong .env'
            );
        }

        return $key;
    }
}
```
