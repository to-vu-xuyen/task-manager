<?php

namespace common\repositories\task;

use Yii;
use common\models\task\Task;
use common\repositories\BaseRepositoryInterface;
use common\repositories\task\interfaces\TaskRepositoryInterface;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;

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
    public function findById(int $id, ?int $userId = null): Task
    {
        $task = Task::find()
            ->where(['id' => $id]);
        if($userId) {
            $task->andWhere(['or',
                ['user_id' => $userId],
                ['assignee_id' => $userId],
            ]);
        }
        $task = $task->one();
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



    public function search(array $filter = [], int $pageSize = 20): DataProviderInterface
    {
        $query = Task::find();
        
        $query->andFilterWhere(['id' => $filter['id'] ?? null]);
        $query->andFilterWhere(['user_id' => $filter['user_id'] ?? null]);
        $query->andFilterWhere(['assginee_id' => $filter['assginee_id'] ?? null]);
        $query->andFilterWhere(['like', 'title', $filter['title'] ?? null]);
        $query->andFilterWhere(['like', 'description', $filter['description'] ?? null]);
        $query->andFilterWhere(['like', 'content', $filter['content'] ?? null]);
        $query->andFilterWhere(['status' => $filter['status'] ?? null]);

        // Check ngày tạo trong khoảng
        if (!empty($filter['created_from'])) {
            $query->andWhere(['>=', 'created_at', $filter['created_from'] . ' 00:00:00']);
        }
        if (!empty($filter['created_to'])) {
            $query->andWhere(['<=', 'created_at', $filter['created_to'] . ' 23:59:59']);
        }

        // Check ngày hết hạn trong khoảng
        if (!empty($filter['due_from'])) {
            $query->andWhere(['>=', 'due_at', $filter['due_from']]);
        }
        if (!empty($filter['due_to'])) {
            $query->andWhere(['<=', 'due_at', $filter['due_to'] . ' 23:59:59']);
        }
        
        // Check ngày hết hạn so với hiện tại
        if (!empty($filter['overdue']) && $filter['overdue']) {
            $query->andWhere(['<', 'due_at', date('Y-m-d H:i:s')]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => ['id', 'title', 'status', 'created_at', 'due_at'],
            ]

        ]);
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
