<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task}}`.
 */
class m260107_030850_create_task_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        $this->createTable('{{%task}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'assignee_id' => $this->integer(),
            'title' => $this->string()->notNull(),
            'description' => $this->string(),
            'content' => $this->text(),
            'status' => $this->string(20)->notNull()->defaultValue(Task::STATUS_ACTIVE),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime(),
            'deleted_at' => $this->datetime(),
        ], $tableOptions);

        // Tạo index
        $this->createIndex(
            '{{%idx-task-user_id}}',
            '{{%task}}',
            'user_id'
        );

        // Tạo khóa ngoại
        $this->addForeignKey(
            '{{%fk-task-user_id}}', // Tên rằng buộc
            '{{%task}}',            // Table gắn khóa ngoại
            'user_id',              // Khóa ngoại
            '{{%user}}',            // Table của khóa ngoại (ref table)
            'id',                   // Field của khóa ngoại (ref field)
            'RESTRICT',             // ON DELETE
            'CASCADE',              // ON UPDATE
        );

        // Tạo index
        $this->createIndex(
            '{{%idx-task-assignee_id}}',
            '{{%task}}',
            'assignee_id'
        );

        // Tạo khóa ngoại
        $this->addForeignKey(
            '{{%fk-task-assignee_id}}', // Tên rằng buộc
            '{{%task}}',            // Table gắn khóa ngoại
            'assignee_id',              // Khóa ngoại
            '{{%user}}',            // Table của khóa ngoại (ref table)
            'id',                   // Field của khóa ngoại (ref field)
            'SET NULL',             // ON DELETE
            'CASCADE',              // ON UPDATE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-task-user_id}}', '{{%task}}');
        $this->dropForeignKey('{{%fk-task-assignee_id}}', '{{%task}}');
        $this->dropTable('{{%task}}');
    }
}
