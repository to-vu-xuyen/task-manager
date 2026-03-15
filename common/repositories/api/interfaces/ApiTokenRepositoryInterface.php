<?php

namespace common\repositories\api\interfaces;

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

    public function generateToken(
        int $userId,
        string $type = ApiToken::TYPE_API_KEY,
        ?string $name = null,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): UserApiToken;

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
