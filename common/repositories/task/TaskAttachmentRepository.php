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
            if (!$taskAttachment->save(false)) {
                $transaction->rollBack();
                throw new \RuntimeException('Cannot save Task Attachments');
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function delete(TaskAttachment $taskAttachment): bool
    {
        // $taskAttachment = TaskAttachment::find()->where(['task_id' => $taskId])->all();
        // $transaction = Yii::$app->db->beginTransaction();
        try {
            // foreach ($taskAttachment as $attachment) {
            $taskAttachment->delete();
            // }
            // $transaction->commit();
            return true;
        } catch (\Exception $e) {
            // $transaction->rollBack();
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function deleteAllByTaskId(int $taskId): bool
    {
        $taskAttachments = $this->getByTaskId($taskId);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($taskAttachments as $taskAttachment) {
                $taskAttachment->delete();
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function getByTaskId(int $taskId): array
    {
        return TaskAttachment::find()->where(['task_id' => $taskId])->orderBy(['id' => SORT_DESC])->all();
    }

    public function getById(int $id): ?TaskAttachment
    {
        $attachment = TaskAttachment::findOne($id);
        if ($attachment->deleted_at != null || empty($attachment)) {
            throw new \DomainException("TaskAttachment not found: ID = {$id}");
        }
        return $attachment;
    }
}