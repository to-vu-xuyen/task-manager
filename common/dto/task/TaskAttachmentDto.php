<?php

namespace common\dto\task;

class TaskAttachmentDto
{
    public int $id;
    public int $task_id;
    public int $user_id;
    public string $file_name;
    public string $file_path;
    public string $file_type;
    public int $file_size;
    public string $created_at;
    public ?string $updated_at = null;

    public function __construct(
        int $id,
        int $task_id,
        int $user_id,
        string $file_name,
        string $file_path,
        string $file_type,
        int $file_size,
        string $created_at,
        ?string $updated_at
    ) {
        $this->id = $id;
        $this->task_id = $task_id;
        $this->user_id = $user_id;
        $this->file_name = $file_name;
        $this->file_path = $file_path;
        $this->file_type = $file_type;
        $this->file_size = $file_size;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromModel(TaskAttachment $taskAttachment): self
    {
        return new self(
            $taskAttachment->id,
            $taskAttachment->task_id,
            $taskAttachment->user_id,
            $taskAttachment->file_name,
            $taskAttachment->file_path,
            $taskAttachment->file_type,
            $taskAttachment->file_size,
            $taskAttachment->created_at,
            $taskAttachment->updated_at
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}