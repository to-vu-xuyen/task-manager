<?php

namespace common\dto\activitylog;

use common\models\activitylog\ActivityLog;

class ActivityLogDto
{
    public readonly int $id;
    public readonly int $user_id;
    public readonly string $target_type;
    public readonly int $target_id;
    public readonly string $action;
    public readonly ?string $meta;
    public readonly ?string $ip_address;
    public readonly ?string $user_agent;
    public readonly ?string $error_message;
    public readonly string $created_at;

    public function __construct(ActivityLog $activityLog)
    {
        $this->id = $activityLog->id;
        $this->user_id = $activityLog->user_id;
        $this->target_type = $activityLog->target_type;
        $this->target_id = $activityLog->target_id;
        $this->action = $activityLog->action;
        $this->meta = $activityLog->meta;
        $this->ip_address = $activityLog->ip_address;
        $this->user_agent = $activityLog->user_agent;
        $this->error_message = $activityLog->error_message;
        $this->created_at = $activityLog->created_at;
    }

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'user_id' => $this->user_id,
			'target_type' => $this->target_type,
			'target_id' => $this->target_id,
			'action' => $this->action,
			'meta' => $this->meta,
			'ip_address' => $this->ip_address,
			'user_agent' => $this->user_agent,
			'error_message' => $this->error_message,
			'created_at' => $this->created_at,
		];
	}

    public static function collection(array $models): array
    {
        return array_map(fn($m) => new self($m), $models);
    }
}