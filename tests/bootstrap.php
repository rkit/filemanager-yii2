<?php

// ensure we get report on all possible php errors
error_reporting(-1);

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@tests', __DIR__);

return new \yii\console\Application([
    'id' => 'unit',
    'basePath' => __DIR__,
    'components' => [
        'user' => [
            'class' => 'tests\data\User',
            'id' => 0
        ],
        'db' => [
            'class'             => 'yii\db\Connection',
            'dsn'               => 'mysql:host=127.0.0.1;dbname=filemanager_yii2_tests',
            'username'          => 'root',
            'password'          => '',
            'emulatePrepare'    => true,
            'charset'           => 'utf8',
            'enableSchemaCache' => false
        ],
        'fileManager' => [
            'class' => 'rkit\filemanager\FileManager',
            'uploadDirProtected' => __DIR__ . '/tmp/private',
            'uploadDirUnprotected' => __DIR__ . '/tmp/public',
            'publicPath' => 'uploads',
            'ownerTypes' => [
                'news.photo_id' => 1,
                'news.preview' => 2,
                'news.gallery' => 3,
            ]
        ]
    ]
]);
