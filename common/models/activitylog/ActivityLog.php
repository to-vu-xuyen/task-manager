<?php

namespace common\models;

use Yii;
use common\models\User;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%activity_log}}".
 *
 * @property int $id
 * @property int $user_id
 * @property string $action
 * @property string $target_type
 * @property int $target_id
 * @property string|null $meta
 * @property string $created_at
 */
class ActivityLog extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%activity_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['meta'], 'default', 'value' => null],
            [['user_id', 'action', 'target_type', 'target_id', 'created_at'], 'required'],
            [['user_id', 'target_id'], 'integer'],
            [['meta', 'created_at'], 'safe'],
            [['action', 'target_type'], 'string', 'max' => 50],
            [['ip_address', 'user_agent'], 'string', 'max' => 255],
            [['error_message'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'action' => Yii::t('app', 'Action'),
            'target_type' => Yii::t('app', 'Target Type'),
            'target_id' => Yii::t('app', 'Target ID'),
            'meta' => Yii::t('app', 'Meta'),
            'created_at' => Yii::t('app', 'Created At'),
            'ip_address' => Yii::t('app', 'IP Address'),
            'user_agent' => Yii::t('app', 'User Agent'),
            'error_message' => Yii::t('app', 'Error Message'),
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function getUser() {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
