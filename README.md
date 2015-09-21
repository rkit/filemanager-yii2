# FileManager for Yii2

[![Build Status](https://travis-ci.org/rkit/filemanager-yii2.svg?branch=master)](https://travis-ci.org/rkit/filemanager-yii2)
[![Code Coverage](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/?branch=master)
[![codecov.io](http://codecov.io/github/rkit/filemanager-yii2/coverage.svg?branch=master)](http://codecov.io/github/rkit/filemanager-yii2?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/?branch=master)

## IN PROCESS

## Features

- Upload with validations
- Create thumbnails on the fly with cache or after upload
- Easy setup presets for files
- Check the Owner of file
- Storing information about a file in the database

## Installation

   ```
   composer require rkit/filemanager-yii2
   ```

## Basic usage

1. Controller

   ```php
   public function behaviors()
   {
       return [
           'verbs' => [
               'class' => VerbFilter::className(),
               'actions' => [
                   'preview-upload' => ['post']
               ],
           ],
       ];
   }

   public function actions()
   {
       return [
           'preview-upload' => [
               'class'     => 'rkit\filemanager\actions\UploadAction',
               'modelName' => 'app\models\News',
               'attribute' => 'preview',
               'inputName' => 'file',
           ],
       ];
   }
   ```

2. Model

   ```php
   // any component to resize/crop images
   use Intervention\Image\ImageManagerStatic as Image;

   …

   public function behaviors()
   {
       return [
           [
               'class' => 'rkit\filemanager\behaviors\FileBehavior',
               'attributes' => [
                   'preview' => [
                       // save file path in this table
                       'saveFilePath' => true,

                       // save file id in this table
                       // 'saveFileId' => true,

                       // @see http://www.yiiframework.com/doc-2.0/guide-tutorial-core-validators.html
                       'rules' => [
                           'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                           'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                           'extensions' => ['jpg', 'jpeg', 'png'],
                           'maxSize' => 1024 * 1024 * 1, // 1 MB
                           'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
                       ],

                       // presets for the files, can be used on the fly or you can to apply after upload
                       // after applying a preset — the file is saved in the file system
                       'preset' => [
                           '200x200' => function ($realPath, $publicPath, $thumbPath) {
                              // any manipulation on the file
                              Image::make($realPath . $publicPath)
                                   ->fit(200, 200)
                                   ->save($realPath . $thumbPath, 100);
                           },

                           '1000x1000' => function ($realPath, $publicPath, $thumbPath) {
                              // any manipulation on the file
                              Image::make($realPath . $publicPath)
                                   ->resize(1000, 1000, function ($constraint) {
                                       $constraint->aspectRatio();
                                       $constraint->upsize();
                                   })
                                   ->save(null, 100);
                           },
                       ],

                       // * — to apply all presets after upload (or an array with the names[] of presets)
                       'applyPresetAfterUpload' => '*'
                   ]
               ]
           ]
       ];
   }
   ```

## Thumbnails

- Apply a preset and return public path

   ```php
   $model->thumb('preview', '200x200');
   ```

- Apply a preset for a custom path to the file

   ```php
   $model->thumb('preview', '200x200', '/path/to/file');
   ```

- Apply a preset and return real path

   ```php
   $model->thumb('preview', '200x200', null, true);
   ```
