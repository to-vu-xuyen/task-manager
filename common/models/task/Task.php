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

    const STATUS_PENDING = 'pending'; // Đang chờ
    const STATUS_ACTIVE = 'active'; // Đang hoạt động
    const STATUS_IN_PROGRESS = 'in_progress'; // Đang giải quyết
    const STATUS_COMPLETED = 'completed'; // Đã hoàn thành
    const STATUS_ARCHIVED = 'archived'; // Lưu trữ hiển thị nhưng không làm gì
    const STATUS_DELETED = 'deleted'; // Soft Delete
    const STATUS_DRAFT = 'draft'; // Nháp
    // const STATUS_PUBLIC = 'public'; // Công khai
    // const STATUS_PRIVATE = 'private'; // Riêng tư
    const STATUS_CANCELLED = 'cancelled'; // Đã hủy

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
            [['status'], 'in', 'range' => self::getStatusList()],
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
        if (!$this->due_at || empty($this->due_at)) {
            return false;
        }
        return strtotime($this->due_at) < time() && $this->status !== self::STATUS_COMPLETED;
    }

    public function softDelete(): bool{
        $this->status = self::STATUS_DELETED;
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function isCompleted(): bool{
        return $this->status === self::STATUS_COMPLETED;
    }

    public static function getStatusList(): array{
        return [
            self::STATUS_PENDING => Yii::t('app', 'Pending'),
            self::STATUS_ACTIVE => Yii::t('app', 'Active'),
            self::STATUS_IN_PROGRESS => Yii::t('app', 'In Progress'),
            self::STATUS_COMPLETED => Yii::t('app', 'Completed'),
            self::STATUS_ARCHIVED => Yii::t('app', 'Archived'),
            self::STATUS_DELETED => Yii::t('app', 'Deleted'),
            self::STATUS_DRAFT => Yii::t('app', 'Draft'),
            self::STATUS_CANCELLED => Yii::t('app', 'Cancelled'),
        ];
    }
}
