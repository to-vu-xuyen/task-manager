<?php

namespace common\services\activitylog;

use common\repositories\activitylog\interface\ActivityLogRepositoryInterface;

/**
 * 
 */
interface AcitivityLogServiceInterface 
{
	public function createActivityLog($data);
	public function updateActivityLog($data);
	public function getAllActivityLog();
	public function getActivityLogById($id);
	public function getActivityLogByUserId($userId);
}