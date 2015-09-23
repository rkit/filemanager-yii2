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
- Support for multiple file uploads
- Check the Owner of file
- Storing information about a file in the database

## Installation

1. Installing using Composer
   ```
   composer require rkit/filemanager-yii2
   ```

2. Run migrations
   ```
   php yii migrate --migrationPath=@vendor/rkit/filemanager-yii2/src/migrations/ --interactive=0
   ```

## Configuration

Add the following in your config, in section `components`

```php
'fileManager' => [
    'class' => 'rkit\filemanager\FileManager',
    // directory for files inaccessible from the web
    'uploadDirProtected' => '@app/runtime',
    // directory for files available from the web
    'uploadDirUnprotected' => '@app/web',
    // path in a directory of upload
    'publicPath' => 'uploads',
    // an array of the types of owners, in the format of `table.attribute` => `unique value`
    'ownerTypes' => [
        'news.text' => 1,
        'news.preview' => 2,
        'news.gallery' => 3,
        'user_profile.photo' => 4,
    ]
]
```

## Usage

### Basic usage

1. **Controller**

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
               'type'      => 'image', // `image` or `file` for non-images
               'inputName' => 'file' // the name of the file input field
           ],
       ];
   }
   ```

2. **Model**

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

                       // inaccessible from the web
                       // 'protected' => true,

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

3. **View**

   Any widget for ajax upload

### Thumbnails

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

### Gallery

1. **Controller**

   ```php
   public function actions()
   {
       return [
           'gallery-upload' => [
               'class'     => 'rkit\filemanager\actions\UploadAction',
               'modelName' => 'app\models\News',
               'attribute' => 'gallery',
               'type'      => 'image', // `image` or `file` for non-images
               'inputName' => 'file', // the name of the file input field
               'multiple'  => true, // for multiple files
               'template'  => Yii::getAlias('@app/path/to/file') // path to template for uploaded a file
           ]
       ]
   }
   ```

2. **Template for uploaded a file**

   ```php
   <li>
     <a href="<?= $file->path()?>" target="_blank">
       <img src="<?= $model->thumb('gallery', '80x80', $file->path())?>">
     </a>
     <?= Html::textInput(Html::getInputName($model, $attribute) . '[files][' . $file->id .']', $file->title, [
         'class' => 'form-control',
     ])?>
   </li>
   ```

### Save after upload

For example this could be a need for wysiwyg editor,
when you need to immediately save the file after upload and assign the owner.

```php
public function actions()
{
    return [
        'text-upload' => [
            'class' => 'rkit\filemanager\actions\UploadAction',
            'modelName' => 'app\models\News',
            'attribute' => 'text',
            'type'      => 'image', // `image` or `file` for non-images
            'inputName' => 'file', // the name of the file input field
            'saveAfterUpload' => true, // save the file immediately after upload
            'ownerId' => 0 // set owner id
        ]
    ]
}
```

### Manually create a file from path

```php
object Yii::$app->fileManager->create('/path/to/file', $ownerId, $model->getFileOwnerType($attribute), true);
```

### Manually create a file from URL

```php
object Yii::$app->fileManager->create('http://…/path/to/file', $ownerId, $model->getFileOwnerType($attribute), true);
```

### Manually create a **protected** file from path

```php
object Yii::$app->fileManager->create('/path/to/file', $ownerId, $model->getFileOwnerType($attribute), true, true);
```

### Get files

```php
array $model->getFiles($attribute);
```

### Сheck whether a file is protected

```php
bool $model->isProtected($attribute);
```

### Get a description of rules

```php
string $model->getFileRulesDescription($attribute)
```

Output

```
Min. size of image: 300x300px
File types: JPG, JPEG, PNG
Max. file size: 1.049 MB
```

### Get real path to file

```php
string $model->getFileRealPath($attribute);
```
