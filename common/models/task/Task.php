<?php

namespace common\models\task;

use Yii;
use common\models\User;

/**
 * This is the model class for table "{{%task}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $assignee_id
 * @property string $title
 * @property string|null $description
 * @property string|null $content
 * @property string $status
 * @property string|null $due_at
 * @property string $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 *
 * @property User $assignee
 * @property User $user
 */
class Task extends \yii\db\ActiveRecord {

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_DELETED = 'deleted';
    const STATUS_TRASH = 'trash';
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLIC = 'public';
    const STATUS_PRIVATE = 'private';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName(){
        return '{{%task}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(){
        return [
            [['assignee_id', 'description', 'content', 'due_at', 'updated_at', 'deleted_at'], 'default', 'value' => null],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['user_id', 'title'], 'required'],
            [['user_id', 'assignee_id'], 'integer'],
            [['content'], 'string'],
            [['due_at', 'created_at', 'updated_at', 'deleted_at'], 'safe'],
            [['title', 'description'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_ACTIVE,
                self::STATUS_IN_PROGRESS,
                self::STATUS_COMPLETED,
                self::STATUS_ARCHIVED,
            ]],
            [['assignee_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['assignee_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(){
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'assignee_id' => Yii::t('app', 'Assignee ID'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'content' => Yii::t('app', 'Content'),
            'status' => Yii::t('app', 'Status'),
            'due_at' => Yii::t('app', 'Due At'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'deleted_at' => Yii::t('app', 'Deleted At'),
        ];
    }

    /**
     * Gets query for [[Assignee]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignee(){
        return $this->hasOne(User::class, ['id' => 'assignee_id']);
    }

    /**
     * Gets query for [[User]] (creator).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser(){
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool{
        if (!$this->due_at) {
            return false;
        }
        return strtotime($this->due_at) < time() && $this->status !== self::STATUS_COMPLETED;
    }
}
