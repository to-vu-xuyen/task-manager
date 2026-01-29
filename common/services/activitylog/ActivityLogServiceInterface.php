<?php

namespace common\services\activitylog;

use common\forms\activitylog\ActivityLogCreateForm;
use common\dto\activitylog\ActivityLogDto;


/**
 * 
 */
interface ActivityLogServiceInterface
{
	public function create(ActivityLogCreateForm $form): ?ActivityLogDto;
	public function getAll(): array;
	public function getById($id): ?ActivityLogDto;
	public function getByUserId($userId): array;
	public function getByTarget($targetType, $targetId): array;
}