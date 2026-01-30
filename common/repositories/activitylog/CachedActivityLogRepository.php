<?php

namespace common\repositories\activitylog;

use common\repositories\activitylog\interface\ActivityLogRepositoryInterface;
use yii\caching\TagDependency;

class CachedActivityLogRepository implements ActivityLogRepositoryInterface
{
    protected ActivityLogRepositoryInterface $activityLogRepository;
    private int $duration;
    private const TAG_ALL = 'activity_log';
    private const TAG_TARGET_PREFIX = 'activity_log_target_';
    private const TAG_ACTION_PREFIX = 'activity_log_action_';
    private const TAG_USER_PREFIX = 'activity_log_user_';

    public function __construct(ActivityLogRepositoryInterface $activityLogRepository, int $duration = 300)
    {
        $this->activityLogRepository = $activityLogRepository;
        $this->duration = $duration;
    }

    public function getNewest(int $limit = 10): array
    {
        $cacheKey = self::TAG_ALL . ':newest:' . $limit;
        return Yii::$app->cache->getOrSet($cacheKey, function () use ($limit) {
            return $this->activityLogRepository->getNewest($limit);
        }, $this->duration, new TagDependency(['tags' => [self::TAG_ALL]]));
    }

    public function findById($id): ?ActivityLogDto
    {
        $cacheKey = self::TAG_ALL . ':id:' . $id;
        return Yii::$app->cache->getOrSet($cacheKey, function () use ($id) {
            return $this->activityLogRepository->findById($id);
        }, $this->duration, new TagDependency(['tags' => [self::TAG_ALL]]));
    }

    public function findByUserId(int $user_id, int $limit = 50): array
    {
        $cacheKey = self::TAG_USER_PREFIX . $user_id . ':newest:' . $limit;
        return Yii::$app->cache->getOrSet($cacheKey, function () use ($user_id, $limit) {
            return $this->activityLogRepository->findByUserId($user_id, $limit);
        }, $this->duration, new TagDependency(['tags' => [self::TAG_ALL, self::TAG_USER_PREFIX . $user_id]]));
    }

    public function findByTarget(string $targetType, int $targetId): array
    {
        $cacheKey = self::TAG_TARGET_PREFIX . $targetType . ':' . $targetId;
        return Yii::$app->cache->getOrSet($cacheKey, function () use ($targetType, $targetId) {
            return $this->activityLogRepository->findByTarget($targetType, $targetId);
        }, $this->duration, new TagDependency(['tags' => [self::TAG_ALL, self::TAG_TARGET_PREFIX . $targetType . ':' . $targetId]]));
    }

    public function findByAction(string $action, int $limit = 100): array
    {
        $cacheKey = self::TAG_ACTION_PREFIX . $action . ':newest:' . $limit;
        return Yii::$app->cache->getOrSet($cacheKey, function () use ($action, $limit) {
            return $this->activityLogRepository->findByAction($action, $limit);
        }, $this->duration, new TagDependency(['tags' => [self::TAG_ALL, self::TAG_ACTION_PREFIX . $action]]));
    }

    public function search(array $filter = [], int $pageSize = 20): DataProviderInterface{

        return $this->activityLogRepository->search($filter, $pageSize);
    }

    public function save(ActivityLog $log): void{
        $this->activityLogRepository->save($log);
    }

    public function clearAllCache(): void{
        Yii::$app->cache->invalidate(Yii::$app->cache, [self::TAG_ALL]);
    }

    public function clearCacheByUserId(int $user_id): void{
        Yii::$app->cache->invalidate(Yii::$app->cache, [self::TAG_USER_PREFIX . $user_id]);
    }

    public function clearCacheByTarget(string $targetType, int $targetId): void{
        Yii::$app->cache->invalidate(Yii::$app->cache, [self::TAG_TARGET_PREFIX . $targetType . ':' . $targetId]);
    }

    public function clearCacheByAction(string $action): void{
        Yii::$app->cache->invalidate(Yii::$app->cache, [self::TAG_ACTION_PREFIX . $action]);
    }
    
}