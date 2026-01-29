<?php

namespace common\repositories\activitylog;

use common\repositories\activitylog\interface\ActivityLogRepositoryInterface;

class ActivityLogCachedRepository implements ActivityLogRepositoryInterface
{
    protected ActivityLogRepositoryInterface $activityLogRepository;

    public function __construct(ActivityLogRepositoryInterface $activityLogRepository)
    {
        $this->activityLogRepository = $activityLogRepository;
    }

    public function findById($id): ?ActivityLogDto
    {
        return $this->activityLogRepository->findById($id);
    }

    public function findByUserId(int $user_id, int $limit = 50): array
    {
        return $this->activityLogRepository->findByUserId($user_id, $limit);
    }

    public function findByTarget(string $targetType, int $targetId): array
    {
        return $this->activityLogRepository->findByTarget($targetType, $targetId);
    }

    public function findByAction(string $action, int $limit = 100): array
    {
        return $this->activityLogRepository->findByAction($action, $limit);
    }
}