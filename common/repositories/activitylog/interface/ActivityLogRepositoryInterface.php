<?php
namespace common\repositories\activitylog\interface;
use common\models\activitylog\ActivityLog;
use yii\data\DataProviderInterface;
use common\dto\activitylog\ActivityLogDto;

interface ActivityLogRepositoryInterface{

	public function getNewest(int $limit = 50): array;
	public function findById($id): ?ActivityLogDto;
	public function findByUserId(int $user_id, int $limit = 50): array;
	public function findByTarget(string $targetType, int $targetId): array;
    public function findByAction(string $action, int $limit = 100): array;
	public function save(ActivityLog $log): void;
	public function search(array $filter = [], int $pageSize = 20): DataProviderInterface;
}