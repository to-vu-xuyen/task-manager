<?php
namespace common\repositories\activitylog\interface;
use common\models\activelog\ActivityLog;
use common\models\User;

interface ActivityLogRepositoryInterface{

	public function findById($id): ActivityLog;
	public function findByUserId(int $user_id, int $limit = 50): array;
	public function findByTarget(string $targetType, int $targetId): array;
    public function findByAction(string $action, int $limit = 100): array;
}