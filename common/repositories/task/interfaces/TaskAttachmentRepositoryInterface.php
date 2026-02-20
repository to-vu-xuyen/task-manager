<?php

namespace common\repositories\task\interfaces;

use common\models\task\TaskAttachment;

interface TaskAttachmentRepositoryInterface
{
    public function save(TaskAttachment $taskAttachment): void;

    public function delete(int $taskId): bool;

    public function getByTaskId(int $taskId): array;

    public function getById(int $taskId): TaskAttachment;
}