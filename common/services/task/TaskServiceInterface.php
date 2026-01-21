<?php

namespace common\services\task;

use common\models\task\Task;
use common\forms\task\TaskCreateForm;
use common\forms\task\TaskUpdateForm;

/**
 * TaskServiceInterface - Contract cho Task business operations
 */
interface TaskServiceInterface
{
    /**
     * Tạo task mới
     */
    public function createTask(TaskCreateForm $form): ?Task;
    
    /**
     * Cập nhật task
     */
    public function updateTask(int $taskId, TaskUpdateForm $form): ?Task;
    
    /**
     * Xóa task (soft delete)
     */
    public function deleteTask(int $taskId): bool;
    
    /**
     * Lấy task theo ID
     */
    public function getTask(int $taskId): ?Task;
    
    /**
     * Lấy tất cả tasks của user
     */
    public function getTasksByUser(int $userId): array;
    
    /**
     * Lấy tasks được assign cho user
     */
    public function getTasksByAssignee(int $assigneeId): array;
    
    /**
     * Đổi status của task
     */
    public function changeStatus(int $taskId, string $newStatus): bool;
}
