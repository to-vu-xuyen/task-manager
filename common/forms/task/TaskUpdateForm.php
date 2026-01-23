<?php

namespace common\forms\task;

use yii\base\Model;
use common\models\User;
use common\models\task\Task;

/**
 * TaskUpdateForm - Form để cập nhật Task
 */
class TaskUpdateForm extends Model
{
    public $title;
    public $description;
    public $content;
    public $assignee_id;
    public $due_at;
    public $status;
    public $user_id;
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['assignee_id', 'user_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 255],
            [['content'], 'string'],
            [['due_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['status'], 'in', 'range' => [
                Task::STATUS_PENDING,
                Task::STATUS_ACTIVE,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
            ]],
            [['assignee_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => User::class, 'targetAttribute' => ['assignee_id' => 'id']],
            [['user_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }
    
    /**
     * Load data từ existing Task
     */
    public function loadFromTask(Task $task): void
    {
        $this->title = $task->title;
        $this->user_id = $task->user_id;
        $this->description = $task->description;
        $this->content = $task->content;
        $this->assignee_id = $task->assignee_id;
        $this->due_at = $task->due_at;
        $this->status = $task->status;
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'assignee_id' => 'Assignee',
            'user_id' => 'User',
            'title' => 'Title',
            'description' => 'Description',
            'content' => 'Content',
            'due_at' => 'Due Date',
            'status' => 'Status',
        ];
    }
}
