<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%activity_log}}`.
 */
class m260123_084909_create_activity_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        $this->createTable('{{%activity_log}}', [
            'id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'action' => $this->string(50)->notNull(),
            'target_type' => $this->string(50)->notNull(),
            'target_id' => $this->integer()->notNull(),
            'meta' => $this->json(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);
        $this->addPrimaryKey('pk-activity_log', '{{%activity_log}}', ['id', 'created_at']);
        $this->createIndex(
            '{{%idx-activity_log-user_id}}',
            '{{%activity_log}}',
            'user_id'
        );
        $this->createIndex(
            '{{%idx-activity_log-action}}',
            '{{%activity_log}}',
            'action'
        );
        $this->createIndex(
            '{{%idx-activity_log-target}}',
            '{{%activity_log}}',
            [
                'target_type',
                'target_id'
            ]
        );

        $this->execute('ALTER TABLE {{%activity_log}} MODIFY id INT NOT NULL AUTO_INCREMENT');
        $this->execute($this->buildPartitionSql());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('{{%idx-activity_log-user_id}}', '{{%activity_log}}');
        $this->dropIndex('{{%idx-activity_log-action}}', '{{%activity_log}}');
        $this->dropIndex('{{%idx-activity_log-target}}', '{{%activity_log}}');
        $this->dropTable('{{%activity_log}}');
    }

    private function buildPartitionSql()
    {
        $partitions = [];
        $date = new \DateTime(date('Y-m-01'));
        
        // Tạo 12 partition
        for ($i = 0; $i < 12; $i++) {
            $current = $date->format('Ym');
            $date->modify('+1 month');
            $next = $date->format('Ym');   
            
            $partitions[] = "PARTITION p{$current} VALUES LESS THAN ({$next})";
        }
        
        return "ALTER TABLE {{%activity_log}} PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (" . implode(',', $partitions) . ")";
    }
}
