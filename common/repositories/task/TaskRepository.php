<?php

namespace common\repositories\task;

use Yii;
use common\models\task\Task;
use common\repositories\task\interfaces\TaskRepositoryInterface;

/**
 * TaskRepository - SQL implementation của TaskRepositoryInterface
 * 
 * Xử lý tất cả database operations cho Task entity
 */
class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Tìm task theo ID
     * 
     * @param int $id
     * @return Task
     * @throws \DomainException nếu không tìm thấy
     */
    public function findById(int $id): Task
    {
        $task = Task::findOne($id);
        if (!$task) {
            throw new \DomainException("Task not found: ID = {$id}");
        }
        return $task;
    }
    
    /**
     * Tìm tất cả tasks của một user
     */
    public function findByUserId(int $userId): array
    {
        return Task::find()
            ->where(['user_id' => $userId])
            ->andWhere(['!=', 'status', Task::STATUS_ARCHIVED])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
    
    /**
     * Tìm tất cả tasks được assign cho một user
     */
    public function findByAssigneeId(int $assigneeId): array
    {
        return Task::find()
            ->where(['assignee_id' => $assigneeId])
            ->andWhere(['in', 'status', [Task::STATUS_ACTIVE, Task::STATUS_IN_PROGRESS]])
            ->orderBy(['due_at' => SORT_ASC])
            ->all();
    }
    
    /**
     * Tìm tất cả tasks đang active
     */
    public function findAllActive(): array
    {
        return Task::find()
            ->where(['status' => Task::STATUS_ACTIVE])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
    
    /**
     * Tìm các tasks quá hạn
     */
    public function findOverdue(): array
    {
        return Task::find()
            ->where(['in', 'status', [Task::STATUS_ACTIVE, Task::STATUS_IN_PROGRESS]])
            ->andWhere(['<', 'due_at', date('Y-m-d H:i:s')])
            ->andWhere(['is not', 'due_at', null])
            ->orderBy(['due_at' => SORT_ASC])
            ->limit(50)
            ->all();
    }
    
    /**
     * Lưu task (create hoặc update)
     * 
     * @throws \DomainException nếu validation fail
     * @throws \RuntimeException nếu save fail
     */
    public function save(Task $task): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$task->validate()) {
                throw new \DomainException('Task validation failed: ' . json_encode($task->errors));
            }
            
            if (!$task->save(false)) {
                throw new \RuntimeException('Cannot save Task');
            }
            
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
    
    /**
     * Xóa task (soft delete - chuyển status sang ARCHIVED)
     */
    public function delete(Task $task): void
    {
        if ($task->isNewRecord) {
            throw new \DomainException('Cannot delete unsaved Task');
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $task->status = Task::STATUS_ARCHIVED;
            $task->deleted_at = date('Y-m-d H:i:s');
            $task->save(false);
            
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new \RuntimeException('Failed to delete Task: ' . $e->getMessage(), 0, $e);
        }
    }
}
