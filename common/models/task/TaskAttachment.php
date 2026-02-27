<?php

namespace common\models\task;

use Yii;
use common\models\User;
use common\models\task\Task;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\ActiveQuery;

class TaskAttachment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%task_attachment}}';
    }

    public function rules()
    {
        return [
            [['task_id', 'user_id', 'file_name', 'file_path', 'file_type', 'file_size'], 'required'],
            [['task_id', 'user_id', 'file_size'], 'integer'],
            [['file_name', 'file_path', 'file_type'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
            ['task_id', 'exist', 'targetClass' => Task::class, 'targetAttribute' => 'id'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                // 'value' => date('Y-m-d H:i:s'),
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'user_id' => 'User ID',
            'file_name' => 'File Name',
            'file_path' => 'File Path',
            'file_type' => 'File Type',
            'file_size' => 'File Size',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }


    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getTask(): ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }
}