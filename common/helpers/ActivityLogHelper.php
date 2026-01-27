<?php

namespace common\helpers;

use common\models\activitylog\ActivityLog;
use common\repositories\activitylog\ActivityLogRepositoryInterface;
use common\services\activitylog\ActivityLogServiceInterface;
use common\repositories\activitylog\ActivityLogRepository;
use common\services\activitylog\ActivityLogService;


class ActivityLogHelper
{
    protected $repository;
    protected $service;

    public function __construct(ActivityLogRepositoryInterface $repository, ActivityLogServiceInterface $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public static function instance()
    {
        return Yii::$app->get('activityLogHelper');
    }

    
}