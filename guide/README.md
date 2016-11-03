# Guide

This extension does not contain any widget for ajax upload.  
You can to use any widget, for example: [fileapi-widget-yii2](https://github.com/rkit/fileapi-widget-yii2)  
But what is important, is that **the input value should contain the id of a file or an array of ids.**

## Usage

For example, we have a model `News`, and we want to add the ability to upload files.  
Let's do it.

1. **Configuring components**

   ```php
   'fileManager' => [
       'class' => 'rkit\filemanager\FileManager',
       // 'sessionName' => 'filemanager.uploads',
   ],
   // any flysystem component for storage, for example https://github.com/creocoder/yii2-flysystem
   'localFs' => [
       'class' => 'creocoder\flysystem\LocalFilesystem',
       'path' => '@webroot/uploads',
   ],
   ```

2. **Creating migrations**

   Migration for a model `File` (and of course you need to create the model)

   ```php
   php yii migrate/create create_file_table --fields="title:string:notNull:defaultValue(''),name:string:notNull:defaultValue(''),date_create:timestamp,date_update:timestamp"
   ```
   > You can add any extra fields, such as `user_id`, `ip`, `size`, `extension`

   Migration for a join table

   ```php
   php yii migrate/create create_news_files_table --fields="news_id:integer:notNull:defaultValue(0),file_id:integer:notNull:defaultValue(0)"
   ```
   > You can add any extra fields, such as `type` to divide files by type or `position` to set sort order

3. **Applying Migrations**

   ```php
   php yii migrate
   ```

4. **Declaring a relation**

   ```php
   public function getFiles($callable = null)
   {
        return $this
            ->hasMany(File::className(), ['id' => 'file_id'])
            ->viaTable('news_files', ['news_id' => 'id'], $callable);
   }
   ```

5. **Adding upload action**

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
               'class' => 'rkit\filemanager\actions\UploadAction',
               'modelClass' => 'app\models\News',
               'attribute' => 'preview',
               'inputName' => 'file',
           ],
       ];
   }
   ```

6. **Adding behavior**

   ```php
   public function behaviors()
   {
       return [
           [
               'class' => 'rkit\filemanager\behaviors\FileBehavior',
               'attributes' => [
                   'preview' => [
                       // any flysystem component for storage
                       'storage' => 'localFs',
                       // the base URL for files
                       'baseUrl' => '@web/uploads',
                       // file type (default `image`)
                       'type' => 'image',
                       // relation name
                       'relation' => 'files',
                       // save file path in attribute (default `false`)
                       'saveFilePathInAttribute' => true,
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
                       // core validators
                       'rules' => [
                           'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                           'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                           'extensions' => ['jpg', 'jpeg', 'png'],
                           'maxSize' => 1024 * 1024 * 1, // 1 MB
                           'maxFiles' => 1,
                           'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
                       ]),
                       // the names[] of presets with callbacks
                       // `function(string $realPath, string $publicPath, string $thumbPath)`
                       'preset' => [
                           '220x220' => function ($realPath, $publicPath, $thumbPath) {
                                // you can to use any library for manipulating with files
                                Image::make($realPath . $publicPath)
                                    ->fit(220, 220)
                                    ->save($realPath . $thumbPath, 100);
                           },
                       ]),
                       // the names[] of presets or `*` to apply all
                       'applyPresetAfterUpload' => ['220x220']
                   ],
               ]
           ]
       ];
   }
   ```

## Behavior settings

```php
// any flysystem component for storage
'storage' => 'localFs',
// the base URL for files
'baseUrl' => '@web/uploads',
// file type (default `image`)
'type' => 'image',
// multiple files (default `false`)
'multiple' => false,
// path to template for upload response (the default is an array with id and path of file)
'template' => null,
// a relation name
'relation' => 'files',
// save file path in attribute (default `false`)
'saveFilePathInAttribute' => true,
// save file id in attribute (default `false`)
'saveFileIdInAttribute' => false,
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
'updateFile' => function ($file) {
    // you can modify attributes (without saving)
    return $file;
},
// a callback for filling extra fields, triggered every time after saving model
// `function(ActiveRecord $file, array $fields): array new extra fields`
'extraFields' => function ($file, $fields) {
    return [
         'type' => 1, if you want to divide files by type
         'position' => $positions[$file->id], // if you want to set sort order
    ];
},
// a callback for customizing the relation associated with the junction table
// `function(ActiveQuery $query): ActiveQuery`
'relationQuery' => function ($query) {
    return $query->andWhere(['type' => 1]); // to select a specific type of file
},
// core validators
'rules' => [
    'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
    'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
    'extensions' => ['jpg', 'jpeg', 'png'],
    'maxSize' => 1024 * 1024 * 1, // 1 MB
    'maxFiles' => 1,
    'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
]),
// the names[] of presets with callbacks
// `function(string $realPath, string $publicPath, string $thumbPath)`
'preset' => [
    '220x220' => function ($realPath, $publicPath, $thumbPath) {
         // you can to use any library for manipulating with files
         Image::make($realPath . $publicPath)
             ->fit(220, 220)
             ->save($realPath . $thumbPath, 100);
    },
]),
// the names[] of presets or `*` to apply all
'applyPresetAfterUpload' => ['220x220']
```

### API

To get file

```php
$model->file('preview');
```

If multiple files

```php
$model->allFiles('gallery');
```

To get file full path

```php
$model->filePath('preview');
```

To get file url

```php
$model->fileUrl('gallery');
```

To create a file manually

```php
$model->createFile('preview', '/path/to/file', 'title');
```

To create thumbnail and return url

```php
$model->thumbUrl('preview', '200x200');
```

To create thumbnail and return full path

```php
$model->thumbPath('preview', '200x200');
```

To get extra fields

```php
$model->fileExtraFields('preview');
```

> [See more in API](../api)
