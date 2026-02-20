<?php

namespace common\repositories\task;

use Yii;
use common\models\task\TaskAttachment;
use common\repositories\task\interfaces\TaskAttachmentRepositoryInterface;

class TaskAttachmentRepository implements TaskAttachmentRepositoryInterface
{
    public function save(TaskAttachment $taskAttachment): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$taskAttachment->validate()) {
                throw new \DomainException(
                    'Attachment validation failed: ' . json_encode($taskAttachment->errors)
                );
            }
            if (!$taskAttachment->save()) {
                $transaction->rollBack();
                throw new \RuntimeException('Cannot save Task Attachments');
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function delete(int $taskId): bool
    {
        $taskAttachment = TaskAttachment::find()->where(['task_id' => $taskId])->all();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($taskAttachment as $attachment) {
                $attachment->delete();
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \RuntimeException($e->getMessage());
        }
        return false;
    }

    public function getByTaskId(int $taskId): array
    {
        return TaskAttachment::find()->where(['task_id' => $taskId])->all();
    }

    public function getById(int $taskId): TaskAttachment
    {
        return TaskAttachment::findOne($taskId);
    }
}