<?php

namespace common\dto\task;

use common\models\task\Task;

/**
 * TaskDto - Data Transfer Object cho Task
 * 
 * Dùng để control dữ liệu trả về cho API/View.
 * Không expose sensitive hoặc internal fields.
 */
class TaskDto
{
    public int $id;
    public string $title;
    public ?string $description;
    public ?string $content;
    public string $status;
    public ?string $dueAt;
    public string $createdAt;
    public ?string $updatedAt;
    public bool $isOverdue;
    
    // Creator info
    public int $userId;
    public ?string $userName;
    
    // Assignee info
    public ?int $assigneeId;
    public ?string $assigneeName;
    
    /**
     * Create DTO from Task model
     */
    public static function fromModel(Task $task): self
    {
        $dto = new self();
        
        $dto->id = $task->id;
        $dto->title = $task->title;
        $dto->description = $task->description;
        $dto->content = $task->content;
        $dto->status = $task->status;
        $dto->dueAt = $task->due_at;
        $dto->createdAt = $task->created_at;
        $dto->updatedAt = $task->updated_at;
        $dto->isOverdue = $task->isOverdue();
        
        // Creator
        $dto->userId = $task->user_id;
        $dto->userName = $task->user?->username;
        
        // Assignee
        $dto->assigneeId = $task->assignee_id;
        $dto->assigneeName = $task->assignee?->username;
        
        return $dto;
    }
    
    /**
     * Convert DTO to array (for API response)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'status' => $this->status,
            'dueAt' => $this->dueAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'isOverdue' => $this->isOverdue,
            'user' => [
                'id' => $this->userId,
                'name' => $this->userName,
            ],
            'assignee' => $this->assigneeId ? [
                'id' => $this->assigneeId,
                'name' => $this->assigneeName,
            ] : null,
        ];
    }
    
    
    public static function fromModels(array $tasks): array {
        /* 
        foreach ($tasks as $task) {
            $result[] = self::fromModel($task);
        }
        */
        return array_map(fn(Task $task) => self::fromModel($task), $tasks);
    }
}
