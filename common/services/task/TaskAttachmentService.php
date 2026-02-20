<?php

namespace common\services\task;

use Yii;
use common\models\task\TaskAttachment;
use common\forms\task\TaskAttachmentForm;
use common\repositories\task\interfaces\TaskAttachmentRepositoryInterface;
use common\services\task\TaskAttachmentServiceInterface;

class TaskAttachmentService implements TaskAttachmentServiceInterface
{
    private const UPLOAD_DIR = 'uploads/task';
    protected TaskAttachmentRepositoryInterface $repository;

    public function __construct(TaskAttachmentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function upload(TaskAttachmentForm $form): ?array
    {
        if (!$form->validate()) {
            throw new \DomainException(
                'Attachment validation failed: ' . json_encode($form->errors)
            );
        }

        $taskDir = self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $form->task_id;
        $uploadPath = Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $taskDir;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $attachments = [];
        foreach ($form->files as $file) {
            $uniqueName = uniqid('task_att_', true) . '.' . $file->extension;
            $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $uniqueName;

            if (!$file->saveAs($fullPath)) {
                // throw new \RuntimeException('Cannot save file: ' . $file->name);
                Yii::error('Cannot save file: ' . $file->name);
                continue;
            }

            // $transaction = Yii::$app->db->beginTransaction();
            try {

                $taskAttachment = new TaskAttachment();
                $taskAttachment->task_id = $form->task_id;
                $taskAttachment->user_id = $form->user_id;
                $taskAttachment->file_name = $file->name;
                $taskAttachment->file_path = $taskDir . DIRECTORY_SEPARATOR . $uniqueName;
                $taskAttachment->file_type = $file->type;
                $taskAttachment->file_size = $file->size;

                $this->repository->save($taskAttachment);
                $attachments[] = $taskAttachment;


            } catch (\Throwable $th) {
                @unlink($fullPath);
                Yii::error('Error saving file: ' . $th->getMessage());
                // throw $th;
            }
        }


        return $attachments;
    }

    public function getByTaskId(int $taskId): array
    {
        return $this->repository->getByTaskId($taskId);
    }

    public function deleteAttachment(int $attachmentId): bool
    {
        $attachment = $this->repository->getById($attachmentId);
        $this->deleteFile($attachment->file_path);
        $this->repository->delete($attachment->id);
        return true;
    }

    public function deleteAllByTaskId(int $taskId): bool
    {
        $attachments = $this->repository->getByTaskId($taskId);

        foreach ($attachments as $attachment) {
            $this->deleteFile($attachment->file_path);
            $this->repository->delete($attachment->id);
        }

        return true;
    }

    private function deleteFile(string $relativePath): void
    {
        $filePath = Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $relativePath;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}