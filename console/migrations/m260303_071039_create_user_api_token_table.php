<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_api_token}}`.
 */
class m260303_071039_create_user_api_token_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%user_api_token}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token' => $this->string(255)->notNull(),
            'name' => $this->string(255)->null()->comment('Tên mô tả, chỉ cho API key'),
            'type' => $this->string(20)->notNull()->defaultValue('api_key')->comment('api_key | refresh_token'),
            'scopes' => $this->text()->null()->comment('JSON array of scopes'),
            'expires_at' => $this->dateTime()->null(),
            'last_used_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            '{{%fk-user_api_token-user_id}}',
            '{{%user_api_token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex('{{%idx-user_api_token-token}}', '{{%user_api_token}}', 'token', true);
        $this->createIndex('{{%idx-user_api_token-user_id_type}}', '{{%user_api_token}}', ['user_id', 'type']);
        $this->createIndex('{{%idx-user_api_token-expires_at}}', '{{%user_api_token}}', 'expires_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user_api_token}}');
    }
}
