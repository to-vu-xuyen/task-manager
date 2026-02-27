<?php

namespace common\services\task;

use common\forms\task\TaskAttachmentForm;
use common\models\task\TaskAttachment;

interface TaskAttachmentServiceInterface
{
    public function getById(int $attachmentId): TaskAttachment;

    public function upload(TaskAttachmentForm $form): array;

    public function getByTaskId(int $taskId): array;

    public function deleteAttachment(int $attachmentId): bool;

    public function deleteAllByTaskId(int $taskId): bool;

    public function getDownloadPath(int $attachmentId): string;
}