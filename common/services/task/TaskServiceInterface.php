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
    public function create(TaskCreateForm $form): ?Task;
    
    /**
     * Cập nhật task
     */
    public function update(int $taskId, TaskUpdateForm $form): ?Task;
    
    /**
     * Xóa task (soft delete)
     */
    public function delete(int $taskId): bool;
    
    /**
     * Lấy task theo ID
     */
    public function getById(int $taskId): ?Task;
    
    /**
     * Lấy tất cả tasks của user
     */
    public function getByUserId(int $userId): array;
    
    /**
     * Lấy tasks được assign cho user
     */
    public function getByAssigneeId(int $assigneeId): array;
    
    /**
     * Đổi status của task
     */
    public function changeStatus(int $taskId, string $newStatus): bool;
}
