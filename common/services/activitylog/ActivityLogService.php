<?php

namespace common\services\activitylog;

use common\models\activitylog\ActivityLog;
use common\forms\activitylog\ActivityLogCreateForm;
use common\dto\activitylog\ActivityLogDto;
use common\services\activitylog\ActivityLogServiceInterface;
use common\repositories\activitylog\interface\ActivityLogRepositoryInterface;

class ActivityLogService implements ActivityLogServiceInterface
{
	protected ActivityLogRepositoryInterface $activityLogRepository;

	public function __construct(ActivityLogRepositoryInterface $activityLogRepository)
	{
		$this->activityLogRepository = $activityLogRepository;
	}

    public function create(ActivityLogCreateForm $form): ?ActivityLogDto
    {
        
        if (!$form->validate()) {
            return null;
        }

        $activityLog = new ActivityLog();
        $activityLog->setAttributes($form->attributes);

        if(!empty($form->meta)) {
            $activityLog->meta = json_encode($form->meta);
        }
 

        $this->activityLogRepository->save($activityLog);


        return new ActivityLogDto($activityLog);
    }
    
    public function getAll(): array
    {
        return $this->activityLogRepository->getNewest(20);
    }

    public function getById($id): ?ActivityLogDto
    {
        return $this->activityLogRepository->findById($id);
    }

    public function getByUserId($userId): array
    {
        return $this->activityLogRepository->findByUserId($userId);
    }

    public function getByTarget($targetType, $targetId): array
    {
        return $this->activityLogRepository->findByTarget($targetType, $targetId);
    }
}