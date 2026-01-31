<?php
return [
    'modules' => [
        'gridview' => [
            'class' => 'kartik\grid\Module',
        ],
        'datecontrol' =>  [
            'class' => '\kartik\datecontrol\Module'
        ]
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'authManager' => [
            'class' => yii\rbac\DbManager::class,
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'db' => require __DIR__ . '/db.php',
    ],

    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error', 'warning'],
                'logFile' => '@runtime/logs/error/' . date('Y-m-d') . '.log',
                'maxFileSize' => 10240,
                'maxLogFiles' => 60,
            ],
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['info'],
                'logFile' => '@runtime/logs/info/' . date('Y-m-d') . '.log',
                'maxFileSize' => 10240,
                'maxLogFiles' => 60,
            ],
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error', 'warning', 'info'],
                'logFile' => '@runtime/logs/activitylog/' . date('Y-m-d') . '.log',
                'categories' => ['activitylog'],
                'maxFileSize' => 10240, // 10MB
                'maxLogFiles' => 60, // Tối đa 60 Files
            ],

        ],
    ],
    
    'container' => [
        'definitions' => [
            \common\services\user\auth\AuthServiceInterface::class => [
                'class' => \common\services\user\auth\AuthService::class,
            ],
            
            \common\services\user\UserServiceInterface::class => [
                'class' => \common\services\user\UserService::class,
            ],

            \common\repositories\task\interfaces\TaskRepositoryInterface::class => [
                'class' => \common\repositories\task\TaskRepository::class,
            ],

            \common\services\task\TaskServiceInterface::class => [
                'class' => \common\services\task\TaskService::class,
            ],

            'common\repositories\activitylog\interface\ActivityLogRepositoryInterface' => function($container) {
                $baseRepo = new \common\repositories\activitylog\ActivityLogRepository();
                return new \common\repositories\activitylog\CachedActivityLogRepository($baseRepo);
            },

            \common\services\activitylog\interface\ActivityLogServiceInterface::class => [
                'class' => \common\services\activitylog\ActivityLogService::class,
            ],

            // \common\repositories\task\interfaces\TaskRepositoryInterface::class => \common\repositories\task\TaskRepository::class,
            // \common\services\task\TaskServiceInterface::class => \common\services\task\TaskService::class,
        ],
    ],
];
