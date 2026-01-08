<?php

namespace app\models\task;

use Yii;

/**
 * This is the model class for table "{{%task}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $assignee_id
 * @property string $title
 * @property string|null $description
 * @property string|null $content
 * @property string|null $due_at
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property User $assignee
 * @property User $user
 */
class Task extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%task}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['assignee_id', 'description', 'content', 'due_at', 'updated_at'], 'default', 'value' => null],
            [['user_id', 'title', 'created_at'], 'required'],
            [['user_id', 'assignee_id'], 'integer'],
            [['content'], 'string'],
            [['due_at', 'created_at', 'updated_at'], 'safe'],
            [['title', 'description'], 'string', 'max' => 255],
            [['assignee_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['assignee_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
            'assignee_id' => Yii::t('app', 'Assignee ID'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'content' => Yii::t('app', 'Content'),
            'due_at' => Yii::t('app', 'Due At'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Assignee]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignee()
    {
        return $this->hasOne(User::class, ['id' => 'assignee_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
