<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'api\controllers',


    'modules' => [
        'v1' => [
            'class' => 'api\modules\v1\Module',
        ],
    ],
    
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    
    
    'components' => [
        'request' => [
            'baseUrl' => '/api',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false,
        ],

        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],

        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],

        'authManager' => [
            'class' => yii\rbac\DbManager::class,
        ],

        
        'errorHandler' => [
            'class' => 'api\components\ApiErrorHandler',
        ],

        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'db' => require __DIR__ . '/db.php',

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/api/' . date('Y-m-d') . '.log',
                    'maxFileSize' => 10240, // 10 MB
                    'maxLogFiles' => 30,
                ],
            ],
        ],
    ],
];
