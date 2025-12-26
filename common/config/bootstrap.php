<?php
Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');


// Load .env file
$env_path = __DIR__ . '/../..';
$dotenv = Dotenv\Dotenv::createUnSafeImmutable($env_path);
if(file_exists($env_path . '/.env')){
	$dotenv->load();
}