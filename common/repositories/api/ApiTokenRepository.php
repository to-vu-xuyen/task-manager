<?php

namespace common\repositories\api;

use Yii;
use common\models\user\UserApiToken;
use common\repositories\api\interfaces\ApiTokenRepositoryInterface;

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
                'type' => $type,
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
                'id' => $id,
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
            ->where(['user_id' => $userId]);

        if ($type !== null) {
            $query->andWhere(['type' => $type]);
        }

        return $query->all();
    }

    public function generateToken(
        int $userId,
        string $type = ApiToken::TYPE_API_KEY,
        ?string $name = null,
        ?array $scopes = null,
        ?int $expiresInSeconds = null
    ): UserApiToken {
        return UserApiToken::generateToken($userId, $type, $name, $scopes, $expiresInSeconds);
    }

    /**
     * @inheritdoc
     */
    public function save(UserApiToken $token): void
    {
        if (!$token->save()) {
            throw new \RuntimeException('Failed to save API token: ' . implode(', ', $token->getFirstErrors()));
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(UserApiToken $token): void
    {
        if (!$token->delete()) {
            throw new \RuntimeException('Failed to delete API token: ' . implode(', ', $token->getFirstErrors()));
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteExpired(): int
    {
        return UserApiToken::deleteAll([
            'and',
            ['expires_at' => new \yii\db\Expression('NOT NULL')],
            ['<', 'expires_at', date('Y-m-d H:i:s')],
        ]);
    }
}