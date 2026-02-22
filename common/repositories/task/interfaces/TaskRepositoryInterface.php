<?php

namespace common\repositories\task\interfaces;

use common\models\task\Task;
use yii\data\DataProviderInterface;

/**
 * TaskRepositoryInterface - Contract cho Task data access
 */
interface TaskRepositoryInterface
{
    /**
     * Tìm task theo ID
     * @throws \DomainException nếu không tìm thấy
     */
    public function findById(int $id): Task;

    /**
     * Tìm tất cả tasks của một user
     */
    public function findByUserId(int $userId): array;
    public function findByIdForUser(int $taskId, int $userId): Task;

    /**
     * Tìm tất cả tasks được assign cho một user
     */
    public function findByAssigneeId(int $assigneeId): array;

    /**
     * Tìm tất cả tasks đang active
     */
    public function findAllActive(): array;

    /**
     * Tìm các tasks quá hạn
     */
    public function findOverdue(): array;

    /**
     * Lưu task (create hoặc update)
     */
    public function save(Task $task): void;

    /**
     * Xóa task (soft delete)
     */
    public function delete(Task $task): void;

    /**
     * Tìm kiếm tasks
     */
    public function search(array $filter = [], int $pageSize = 20): DataProviderInterface;
}
