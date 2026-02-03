<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use common\helpers\ActivityLogger;
use yii\filters\AccessControl;

class BaseController extends Controller {

    protected ActivityLogger $activityLogger;

    public function init()
    {
        parent::init();
        $this->activityLogger = Yii::$container->get(ActivityLogger::class);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Chỉ cho phép user đã đăng nhập
                    ],
                ],
            ],
        ];
    }
}