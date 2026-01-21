<?php

namespace common\services\task;

use Yii;
use common\models\task\Task;
use common\forms\task\TaskCreateForm;
use common\forms\task\TaskUpdateForm;
use common\repositories\task\TaskRepository;
use common\repositories\task\interfaces\TaskRepositoryInterface;

/**
 * TaskService - Facade cho Task business operations
 * 
 * Xử lý tất cả business logic liên quan đến Task.
 * Controller chỉ cần tương tác với service này.
 */
class TaskService implements TaskServiceInterface
{
    private TaskRepositoryInterface $repository;
    
    public function __construct(?TaskRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new TaskRepository();
    }
    
    /**
     * Tạo task mới
     */
    public function createTask(TaskCreateForm $form): ?Task
    {
        if (!$form->validate()) {
            return null;
        }
        
        $task = new Task();
        $task->user_id = $form->user_id;
        $task->assignee_id = $form->assignee_id;
        $task->title = $form->title;
        $task->description = $form->description;
        $task->content = $form->content;
        $task->due_at = $form->due_at;
        $task->status = Task::STATUS_PENDING;
        $task->created_at = date('Y-m-d H:i:s');
        
        $this->repository->save($task);
        
        return $task;
    }
    
    /**
     * Cập nhật task
     */
    public function updateTask(int $taskId, TaskUpdateForm $form): ?Task
    {
        if (!$form->validate()) {
            return null;
        }
        
        $task = $this->repository->findById($taskId);
        
        $task->title = $form->title;
        $task->description = $form->description;
        $task->content = $form->content;
        $task->assignee_id = $form->assignee_id;
        $task->due_at = $form->due_at;
        $task->updated_at = date('Y-m-d H:i:s');
        
        $this->repository->save($task);
        
        return $task;
    }
    
    /**
     * Xóa task (soft delete)
     */
    public function deleteTask(int $taskId): bool
    {
        try {
            $task = $this->repository->findById($taskId);
            $this->repository->delete($task);
            return true;
        } catch (\DomainException $e) {
            return false;
        }
    }
    
    /**
     * Lấy task theo ID
     */
    public function getTask(int $taskId): ?Task
    {
        try {
            return $this->repository->findById($taskId);
        } catch (\DomainException $e) {
            return null;
        }
    }
    
    /**
     * Lấy tất cả tasks của user (creator)
     */
    public function getTasksByUser(int $userId): array
    {
        return $this->repository->findByUserId($userId);
    }
    
    /**
     * Lấy tasks được assign cho user
     */
    public function getTasksByAssignee(int $assigneeId): array
    {
        return $this->repository->findByAssigneeId($assigneeId);
    }
    
    /**
     * Đổi status của task
     */
    public function changeStatus(int $taskId, string $newStatus): bool
    {
        $allowedStatuses = [
            Task::STATUS_PENDING,
            Task::STATUS_ACTIVE,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_COMPLETED,
        ];
        
        if (!in_array($newStatus, $allowedStatuses)) {
            return false;
        }
        
        try {
            $task = $this->repository->findById($taskId);
            $task->status = $newStatus;
            $task->updated_at = date('Y-m-d H:i:s');
            $this->repository->save($task);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
