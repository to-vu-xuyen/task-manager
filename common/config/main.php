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

            // \common\repositories\task\interfaces\TaskRepositoryInterface::class => \common\repositories\task\TaskRepository::class,
            // \common\services\task\TaskServiceInterface::class => \common\services\task\TaskService::class,
        ],
    ],
];
