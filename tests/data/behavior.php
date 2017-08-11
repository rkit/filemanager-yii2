<?php

use yii\helpers\ArrayHelper;
use Intervention\Image\ImageManagerStatic as Image;
use tests\data\models\File;

return [
    'class' => 'rkit\filemanager\behaviors\FileBehavior',
    'attributes' => [
        'image' => [
            'storage' => 'localFs',
            'baseUrl' => '@tests/tmp/public',
            'type' => 'image',
            'multiple' => false,
            'relation' => 'imageFile',
            'saveFilePathInAttribute' => ArrayHelper::getValue($options, 'saveFilePathInAttribute', true),
            'saveFileIdInAttribute' => ArrayHelper::getValue($options, 'saveFileIdInAttribute', false),
            'templatePath' => function ($file) {
                $date = new \DateTime(is_object($file->date_create) ? null : $file->date_create);
                return '/' . $date->format('Ym') . '/' . $file->id . '/' . $file->name;
            },
            'createFile' => function ($path, $name) {
                $file = new File();
                $file->title = $name;
                $file->generateName(pathinfo($name, PATHINFO_EXTENSION));
                $file->save();
                return $file;
            },
            'updateFile' => ArrayHelper::getValue($options, 'updateFile', function ($file) {
                return $file;
            }),
            'extraFields' => ArrayHelper::getValue($options, 'extraFields', function () {
                return [
                    'type' => 1,
                ];
            }),
            'rules' => ArrayHelper::getValue($options, 'rules', [
                'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                'extensions' => ['jpg', 'jpeg', 'png'],
                'maxSize' => 1024 * 1024 * 1, // 1 MB
                'maxFiles' => ArrayHelper::getValue($options, 'rules.maxFiles', 1),
                'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
            ]),
            'preset' => ArrayHelper::getValue($options, 'preset', [
                '200x200' => function ($realPath, $publicPath, $thumbPath) {
                    Image::make($realPath . $publicPath)
                        ->fit(200, 200)
                        ->save($realPath . $thumbPath, 100);
                },
                '220x220' => function ($realPath, $publicPath, $thumbPath) {
                    Image::make($realPath . $publicPath)
                        ->fit(220, 220)
                        ->save($realPath . $thumbPath, 100);
                },
                '400x400' => function ($realPath, $publicPath, $thumbPath) {
                    Image::make($realPath . $publicPath)
                        ->fit(400, 400)
                        ->save(null, 100);
                },
            ]),
            'applyPresetAfterUpload' => ArrayHelper::getValue($options, 'applyPresetAfterUpload', ['220x220'])
        ],
        'gallery' => [
            'storage' => 'localFs',
            'baseUrl' => '@tests/tmp/public',
            'type' => 'image',
            'multiple' => true,
            'template' => ArrayHelper::getValue($options, 'template', null),
            'relation' => 'galleryFiles',
            'saveFilePathInAttribute' => ArrayHelper::getValue($options, 'saveFilePathInAttribute', true),
            'saveFileIdInAttribute' => ArrayHelper::getValue($options, 'saveFileIdInAttribute', false),
            'templatePath' => function ($file) {
                $date = new \DateTime(is_object($file->date_create) ? null : $file->date_create);
                return '/' . $date->format('Ym') . '/' . $file->id . '/' . $file->name;
            },
            'createFile' => function ($path, $name) {
                $file = new File();
                $file->title = $name;
                $file->generateName(pathinfo($name, PATHINFO_EXTENSION));
                $file->save();
                return $file;
            },
            'updateFile' => ArrayHelper::getValue($options, 'updateFile', function ($file) {
                return $file;
            }),
            'extraFields' => ArrayHelper::getValue($options, 'extraFields', function () {
                return [
                    'type' => 2,
                ];
            }),
            'rules' => ArrayHelper::getValue($options, 'rules', [
                'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                'extensions' => ['jpg', 'jpeg', 'png'],
                'maxSize' => 1024 * 1024 * 1, // 1 MB
                'maxFiles' => ArrayHelper::getValue($options, 'rules.maxFiles', 1),
                'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
            ]),
            'preset' => ArrayHelper::getValue($options, 'preset', [
                '200x200' => function ($realPath, $publicPath, $thumbPath) {
                    Image::make($realPath . $publicPath)
                        ->fit(200, 200)
                        ->save($realPath . $thumbPath, 100);
                },
                '220x220' => function ($realPath, $publicPath, $thumbPath) {
                    Image::make($realPath . $publicPath)
                        ->fit(220, 220)
                        ->save($realPath . $thumbPath, 100);
                },
                '400x400' => function ($realPath, $publicPath, $thumbPath) {
                    Image::make($realPath . $publicPath)
                        ->fit(400, 400)
                        ->save(null, 100);
                },
            ]),
            'applyPresetAfterUpload' => ArrayHelper::getValue($options, 'applyPresetAfterUpload', ['220x220'])
        ],
    ]
];
