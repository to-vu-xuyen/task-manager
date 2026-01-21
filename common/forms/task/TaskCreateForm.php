<?php

namespace common\forms\task;

use yii\base\Model;
use common\models\User;

/**
 * TaskCreateForm - Form để tạo Task mới
 */
class TaskCreateForm extends Model
{
    public $user_id;
    public $assignee_id;
    public $title;
    public $description;
    public $content;
    public $due_at;
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'title'], 'required'],
            [['user_id', 'assignee_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 255],
            [['content'], 'string'],
            [['due_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['assignee_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => User::class, 'targetAttribute' => ['assignee_id' => 'id']],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'Creator',
            'assignee_id' => 'Assignee',
            'title' => 'Title',
            'description' => 'Description',
            'content' => 'Content',
            'due_at' => 'Due Date',
        ];
    }
}
