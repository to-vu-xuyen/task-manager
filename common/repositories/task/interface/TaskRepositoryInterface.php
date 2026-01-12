<?php


namespace common\repositories\task\interface;

use common\repositories\BaseRepositoryInterface;


interface TaskRepositoryInterface extends BaseRepositoryInterface
{
    public function get(int $id): Task;
    public function findByUser(int $userId): array;
    public function findByAssignee(int $assigneeId): array;
    public function findActive(): array;
    public function findOverdue(): array;
}