<?php
return [
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
        // NEW: Updated DI definitions với namespaces mới
        'definitions' => [
            // AuthService - chỉ login/logout
            \common\services\user\auth\AuthServiceInterface::class => [
                'class' => \common\services\user\auth\AuthService::class,
            ],
            // UserService - facade cho user operations
            \common\services\user\UserServiceInterface::class => [
                'class' => \common\services\user\UserService::class,
            ],
        ],
    ],
];
