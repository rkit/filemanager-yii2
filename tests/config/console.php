<?php

Yii::setAlias('@tests', dirname(__DIR__));

$config = [
    'id' => 'unit',
    'basePath' => Yii::getAlias('@tests'),
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
            'uploadDirProtected' => Yii::getAlias('@tests/tmp/private'),
            'uploadDirUnprotected' => Yii::getAlias('@tests/tmp/public'),
            'publicPath' => 'uploads',
            'ownerTypes' => [
                'news.image_path' => 1,
                'news.image_id' => 2,
                'news.image_gallery' => 3,
                'news.image_gallery_protected' => 4,
                'news.image_strict_size' => 5,
                'news.image_min_max_size' => 6,
                'news.image_min_size' => 7,
                'news.image_max_size' => 8,
                'news.image_only_maxwidth' => 9,
                'news.image_only_minwidth' => 10,
                'news.image_only_maxheight' => 11,
                'news.image_only_minheight' => 12
            ]
        ]
    ]
];

if (file_exists(__DIR__ . '/local/config.php')) {
    require_once __DIR__ . '/local/config.php';
}

return $config;
