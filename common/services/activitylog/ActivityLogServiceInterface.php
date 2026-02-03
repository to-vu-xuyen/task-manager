<?php

namespace common\services\activitylog;

use common\forms\activitylog\ActivityLogCreateForm;
use common\dto\activitylog\ActivityLogDto;
use common\models\activitylog\ActivityLog;


/**
 * 
 */
interface ActivityLogServiceInterface
{
	public function create(ActivityLogCreateForm $form): ?ActivityLog;
	public function getAll(): array;
	public function getById(int $id): ?ActivityLog;
	public function getByUserId(int $userId): array;
	public function getByTarget(string $targetType, int $targetId): array;
}