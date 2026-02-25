<?php

namespace common\forms\task;

use Yii;
use yii\base\Model;

class TaskAttachmentForm extends Model
{
    public $task_id;
    public $user_id;

    /**
     * @var UploadedFile[]
     */
    public $files;

    public function formName(): string
    {
        return 'TaskAttachmentForm';
    }
    public function rules()
    {
        return [
            [['task_id', 'user_id'], 'required'],
            [['task_id', 'user_id'], 'integer'],
            [
                ['files'],
                'file',
                'skipOnEmpty' => true,
                'maxFiles' => 5,
                'extensions' => 'jpg, jpeg, png, pdf, doc, docx, xls, xlsx, ppt, pptx',
                'maxSize' => 1024 * 1024 * 10, // 10MB
            ],
        ];
    }


    public function attributeLabels()
    {
        return [
            'task_id' => 'Task ID',
            'user_id' => 'User ID',
            'files' => 'Files',
        ];
    }
}