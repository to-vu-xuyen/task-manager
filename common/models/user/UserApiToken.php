<?php

namespace common\models\user;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * User API Token model
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string|null $name
 * @property string $type
 * @property string|null $scopes
 * @property string|null $expires_at
 * @property string|null $last_used_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read User $user
 */
class UserApiToken extends ActiveRecord
{
    public const TYPE_API_KEY = 'api_key';
    public const TYPE_REFRESH_TOKEN = 'refresh_token';

    public static function tableName()
    {
        return '{{%user_api_token}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules()
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

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'token' => Yii::t('app', 'Token'),
            'name' => Yii::t('app', 'Name'),
            'type' => Yii::t('app', 'Type'),
            'scopes' => Yii::t('app', 'Scopes'),
            'expires_at' => Yii::t('app', 'Expires At'),
            'last_used_at' => Yii::t('app', 'Last Used At'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

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

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Không có expiry = vĩnh viễn
        }
        return strtotime($this->expires_at) < time();
    }

    public function hasScope(string $requiredScope): bool
    {
        if ($this->scopes === null) {
            return true; // Không có scopes = full access
        }

        $scopes = json_decode($this->scopes, true);
        return is_array($scopes) && in_array($requiredScope, $scopes, true);
    }

    public function touch(): void
    {
        $this->updateAttributes(['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public static function findValidToken(string $token): ?self
    {
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
    
    private static function createSecureToken(string $type): string
    {
        $prefix = $type === self::TYPE_REFRESH_TOKEN ? 'rt_' : 'tk_';
        return $prefix . Yii::$app->security->generateRandomString(64);
    }
    
}