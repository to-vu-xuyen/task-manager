<?php

namespace common\helpers;

use Yii;
use common\services\activitylog\ActivityLogServiceInterface;
use common\forms\activitylog\ActivityLogCreateForm;

class ActivityLogger
{
    protected $service;

    public function __construct(ActivityLogServiceInterface $service)
    {
        $this->service = $service;
    }

    // public static function instance()
    // {
    //     return Yii::$app->get('activityLogger');
    // }

    public function log(?array $meta = null, ?string $action = null, ?string $targetType = null, ?int $targetId = null): void
    {
        if(empty($targetType)) {
            $targetType = Yii::$app->controller->id ?? "unknown";
        }
        if(empty($action)) {
            $action = Yii::$app->controller->action->id ?? "unknown";
        }

        if(empty($targetId)) {
            $targetId = 0;
        }
        
        $form = new ActivityLogCreateForm([
            'user_id' => Yii::$app->user->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => $meta,
        ]);
        
        if (!Yii::$app->request->isConsoleRequest) {
            $form->ip_address = Yii::$app->request->userIP;
            $form->user_agent = Yii::$app->request->userAgent;
        }
        try {
            $this->service->create($form);
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), 'activitylog');
        }
    }
    

    
}