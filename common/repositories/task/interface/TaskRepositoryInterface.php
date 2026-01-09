<?php


namespace common\repositories\task\interface;

use common\repositories\BaseRepositoryInterface;


interface TaskRepositoryInterface extends BaseRepositoryInterface
{
    public function get(int $id): Task;
    public function findByUser(int $userId): Task[];        // ← business specific
    public function findActive(): Task[];
    public function findOverdue(): Task[];
}