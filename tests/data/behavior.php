<?php

use yii\helpers\ArrayHelper;
use Intervention\Image\ImageManagerStatic as Image;
use tests\data\models\File;

return [
    'class' => 'rkit\filemanager\behaviors\FileBehavior',
    'attributes' => [
        ArrayHelper::getValue($options, 'field', 'image') => [
            // any flysystem component for storage
            'storage' => 'localFs',
            // the base URL for files
            'baseUrl' => '@tests/tmp/public',
            // file type (default `image`)
            'type' => 'image',
            // multiple files (default `false`)
            'multiple' => ArrayHelper::getValue($options, 'multiple', false),
            // path to template for upload response (the default is an array with id and path of file)
            'template' => ArrayHelper::getValue($options, 'template', null),
            // relation name
            'relation' => 'files',
            // save file path in attribute (default `false`)
            'saveFilePathInAttribute' => ArrayHelper::getValue($options, 'saveFilePathInAttribute', true),
            // save file id in attribute (default `false`)
            'saveFileIdInAttribute' => ArrayHelper::getValue($options, 'saveFileIdInAttribute', false),
            // a callback for generating file path
            // `function(ActiveRecord $file): string`
            'templatePath' => function ($file) {
                $date = new \DateTime(is_object($file->date_create) ? null : $file->date_create);
                return '/' . $date->format('Ym') . '/' . $file->id . '/' . $file->name;
            },
            // a callback for creating `File` model
            // `function(string $path, string $name): ActiveRecord`
            'createFile' => function ($path, $name) {
                $file = new File();
                $file->title = $name;
                $file->generateName(pathinfo($name, PATHINFO_EXTENSION));
                $file->save();
                return $file;
            },
            // a callback for updating `File` model, triggered every time after saving model
            // important: return model without saving
            // `function(ActiveRecord $file): ActiveRecord`
            'updateFile' => ArrayHelper::getValue($options, 'updateFile', function ($file) {
                return $file;
            }),
            // a callback for filling extra fields, triggered every time after saving model
            // `function(ActiveRecord $file, array $fields): array new extra fields`
            'extraFields' => ArrayHelper::getValue($options, 'extraFields', function () {
                return [
                    'type' => 2,
                ];
            }),
            // a callback for customizing the relation associated with the junction table
            // `function(ActiveQuery $query): ActiveQuery`
            'relationQuery' => ArrayHelper::getValue($options, 'relationQuery', function ($query) {
                return $query->andWhere(['type' => 2]);
            }),
            // core validators
            'rules' => ArrayHelper::getValue($options, 'rules', [
                'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                'extensions' => ['jpg', 'jpeg', 'png'],
                'maxSize' => 1024 * 1024 * 1, // 1 MB
                'maxFiles' => ArrayHelper::getValue($options, 'rules.maxFiles', 1),
                'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
            ]),
            // the names[] of presets with callbacks
            // `function(string $realPath, string $publicPath, string $thumbPath)`
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
            // the names[] of presets or `*` to apply all
            'applyPresetAfterUpload' => ArrayHelper::getValue($options, 'applyPresetAfterUpload', ['220x220'])
        ],
    ]
];
