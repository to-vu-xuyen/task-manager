<?php

namespace common\services\activitylog;

use common\models\activitylog\ActivityLog;
use common\forms\activitylog\ActivityLogForm;
use common\services\activitylog\interface\AcitivityLogServiceInterface;
use common\repositories\activitylog\interface\ActivityLogRepositoryInterface;

class ActivityLogService implements AcitivityLogServiceInterface
{
	protected ActivityLogRepositoryInterface $activityLogRepository;

	public function __construct(ActivityLogRepositoryInterface $activityLogRepository)
	{
		$this->activityLogRepository = $activityLogRepository;
	}

    public function create(ActivityLogForm $form): ?ActivityLog
    {
        
        if (!$form->validate()) {
            return null;
        }

        $activityLog = new ActivityLog();
        $activityLog->setAttributes($form->attributes);
 

        $this->activityLogRepository->save($activityLog);


        return $activityLog;
    }

    public function update($data)
    {
        return $this->activityLogRepository->update($data);
    }

    public function getAll()
    {
        return $this->activityLogRepository->getNewest(20);
    }

    public function getById($id)
    {
        return $this->activityLogRepository->findById($id);
    }

    public function getByUserId($userId)
    {
        return $this->activityLogRepository->findByUserId($userId);
    }

    public function getByTarget($targetType, $targetId)
    {
        return $this->activityLogRepository->findByTarget($targetType, $targetId);
    }
}