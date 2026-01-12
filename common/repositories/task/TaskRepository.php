<?php

namespace common\repositories\task;

use Yii;
use yii\db\Exception;
use common\models\Task;
use common\repositories\task\interfaces\TaskRepositoryInterface;

class SqlTaskRepository implements TaskRepositoryInterface{

    private int $cache_duration = 300;
    private string $cacheKeyPrefix = null;

    public function get(int $id): Task
    {
        $task = Task::findOne($id);
        if (!$task) {
            throw new \DomainException("Task not found: ID = {$id}");
        }
        return $task;
    }
    
    /*public function getWithRelations(int $id): Task
    {
        return Task::find()
            ->with(['permissions', 'comments', 'assignee'])
            ->where(['id' => $id])
            ->one() ?? throw new \DomainException("Task not found: ID = {$id}");
    }*/
    
    public function findByUser(int $userId, array $scopes = ['status' => 'active']): array{

        $query = Task::find()->where(['user_id' => $userId]);
        
        if (isset($scopes['status'])) {
            $query->andWhere(['status' => $scopes['status']]);
        }
        if (isset($scopes['due_date'])) {
            $query->andWhere(['<=', 'due_date', $scopes['due_date']]);
        }
        
        return $query->orderBy(['created_at' => SORT_DESC])->all();
    }
    
    public function findActiveByAssignee(int $assigneeId): array
    {
        return Task::find()
            ->where([
                'assignee_id' => $assigneeId,
                'status' => [Task::STATUS_ACTIVE, Task::STATUS_IN_PROGRESS]
            ])
            ->orderBy(['priority' => SORT_DESC, 'due_date' => SORT_ASC])
            ->all();
    }
    
    public function findOverdue(): array
    {
        return Task::find()
            ->where([
                'status' => Task::STATUS_ACTIVE,
                'AND',
                ['<', 'due_date', date('Y-m-d')],
                ['!=', 'assignee_id', null]
            ])
            ->orderBy('due_date')
            ->limit(50)
            ->all();
    }
    
    public function save(Task $task): void
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            // Validate business rules trước khi save
            if (!$task->validate()) {
                throw new \DomainException('Task validation failed: ' . json_encode($task->errors));
            }
            
            if (!$task->save(false)) {  // skip validation vì đã check
                throw new \RuntimeException('Cannot save Task');
            }
            
            // Save relations nếu có (permissions, attachments)
            /*foreach ($task->permissions ?? [] as $permission) {
                $permission->task_id = $task->id;
                $permission->save(false);
            }*/
            
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new \RuntimeException('Failed to save Task: ' . $e->getMessage(), 0, $e);
        }
    }
    
    public function delete(Task $task): void
    {
        if ($task->isNewRecord) {
            throw new \DomainException('Cannot delete new Task');
        }
        
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            // Soft delete
            $task->status = Task::STATUS_ARCHIVED;
            $task->deleted_at = date('Y-m-d H:i:s');
            $task->save(false);
            
            // Cascade delete relations
            // TaskPermission::deleteAll(['task_id' => $task->id]);
            
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new \RuntimeException('Failed to delete Task: ' . $e->getMessage(), 0, $e);
        }
    }
}