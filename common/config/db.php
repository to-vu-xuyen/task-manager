<?php

return [
    'class' => yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;dbname=%s',
        getenv('DB_HOST'),
        getenv('DB_DATABASE')
    ),
    'username' => getenv('DB_USERNAME'),
    'password' => getenv('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'tablePrefix' => 'tm_',
    'enableSchemaCache' => YII_ENV_PROD,
    'schemaCacheDuration' => 86400,
];