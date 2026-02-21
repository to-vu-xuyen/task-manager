<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_attachment}}`.
 */
class m260217_075520_create_task_attachment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_attachment}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'file_name' => $this->string()->notNull(),
            'file_path' => $this->string()->notNull(),
            'file_type' => $this->string()->notNull(),
            'file_size' => $this->integer()->notNull(),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime(),
        ]);

        $this->addForeignKey(
            '{{%fk-task_attachment-task_id}}',
            '{{%task_attachment}}',
            'task_id',
            '{{%task}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%fk-task_attachment-user_id}}',
            '{{%task_attachment}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-task_attachment-task_id}}', '{{%task_attachment}}');
        $this->dropForeignKey('{{%fk-task_attachment-user_id}}', '{{%task_attachment}}');
        $this->dropTable('{{%task_attachment}}');
    }
}
