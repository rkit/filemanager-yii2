# FileManager for Yii2

## Table of Contents

* [File](#file)
    * [setStorage](#setstorage)
    * [getStorage](#getstorage)
    * [generateName](#generatename)
    * [isProtected](#isprotected)
    * [isUnprotected](#isunprotected)
    * [isTmp](#istmp)
    * [getDateOfFile](#getdateoffile)
    * [isOwner](#isowner)
    * [findAllByOwner](#findallbyowner)
    * [findOneByOwner](#findonebyowner)
    * [deleteByOwner](#deletebyowner)
* [FileBehavior](#filebehavior)
    * [uploadDir](#uploaddir)
    * [getFileOwnerType](#getfileownertype)
    * [getFiles](#getfiles)
    * [getFile](#getfile)
    * [isMultiple](#ismultiple)
    * [isFileProtected](#isfileprotected)
    * [getFileRules](#getfilerules)
    * [getFilePreset](#getfilepreset)
    * [getFilePresetAfterUpload](#getfilepresetafterupload)
    * [getFileStorage](#getfilestorage)
    * [generateThumbName](#generatethumbname)
    * [thumb](#thumb)
    * [createFile](#createfile)
    * [getFileRulesDescription](#getfilerulesdescription)
* [FileBind](#filebind)
    * [bindMultiple](#bindmultiple)
* [FileManager](#filemanager)
    * [getOwnerType](#getownertype)
* [FormatValidation](#formatvalidation)
    * [getDescription](#getdescription)
* [LocalStorage](#localstorage)
    * [setFile](#setfile)
    * [getFile](#getfile-1)
    * [path](#path)
    * [save](#save)
    * [saveTemporaryFileToStorage](#savetemporaryfiletostorage)
    * [delete](#delete)

## File

ActiveRecord for table "file"



* Full name: \rkit\filemanager\models\File
* Parent class:


### setStorage

Set a storage

```php
File::setStorage( \rkit\filemanager\Storage $storage ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$storage` | **\rkit\filemanager\Storage** | The Strorage for the file |




---

### getStorage

Get a storage

```php
File::getStorage(  ): string
```







---

### generateName

Generate a name

```php
File::generateName(  ): string
```







---

### isProtected

Checks whether the file is protected

```php
File::isProtected(  ): boolean
```







---

### isUnprotected

Checks whether the file is unprotected

```php
File::isUnprotected(  ): boolean
```







---

### isTmp

Checks whether the file is temp

```php
File::isTmp(  ): boolean
```







---

### getDateOfFile

Get date create of file in format `Ym`

```php
File::getDateOfFile(  ): string
```







---

### isOwner

Checks whether the owner of the file

```php
File::isOwner( integer $ownerId, integer $ownerType ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ownerId` | **integer** | The id of the owner |
| `$ownerType` | **integer** | The type of the owner |




---

### findAllByOwner

Find all by owner

```php
File::findAllByOwner( integer $ownerId, integer $ownerType ): array
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ownerId` | **integer** | The id of the owner |
| `$ownerType` | **integer** | The type of the owner |




---

### findOneByOwner

Find one by owner

```php
File::findOneByOwner( integer $ownerId, integer $ownerType ): \rkit\filemanager\models\File|null
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ownerId` | **integer** | The id of the owner |
| `$ownerType` | **integer** | The type of the owner |




---

### deleteByOwner

Delete by owner

```php
File::deleteByOwner( \rkit\filemanager\Storage $storage, integer $ownerId, integer $ownerType )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$storage` | **\rkit\filemanager\Storage** | The storage of the file |
| `$ownerId` | **integer** | The id of the owner |
| `$ownerType` | **integer** | The type of the owner |




---

## FileBehavior





* Full name: \rkit\filemanager\behaviors\FileBehavior
* Parent class:


### uploadDir

Get the path to the upload directory

```php
FileBehavior::uploadDir( string $attribute ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### getFileOwnerType

Get the type of the owner

```php
FileBehavior::getFileOwnerType( string $attribute ): integer
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### getFiles

Get files

```php
FileBehavior::getFiles( string $attribute ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### getFile

Get the file

```php
FileBehavior::getFile( string $attribute ): \rkit\filemanager\models\File|null
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### isMultiple

Check whether the upload of multiple files

```php
FileBehavior::isMultiple( string $attribute ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### isFileProtected

Checks whether the file is protected

```php
FileBehavior::isFileProtected( string $attribute ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### getFileRules

Get rules

```php
FileBehavior::getFileRules( string $attribute, boolean $onlyCoreValidators = false ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |
| `$onlyCoreValidators` | **boolean** | Only core validators |




---

### getFilePreset

Get the presets of the file

```php
FileBehavior::getFilePreset( string $attribute ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### getFilePresetAfterUpload

Get the presets of the file for apply after upload

```php
FileBehavior::getFilePresetAfterUpload( string $attribute ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### getFileStorage

Get the storage of the file

```php
FileBehavior::getFileStorage( string $attribute ): \rkit\filemanager\behaviors\Storage
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

### generateThumbName

Generate a thumb name

```php
FileBehavior::generateThumbName( string $path, string $prefix ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **string** | The path of the file |
| `$prefix` | **string** | Prefix for name of the file |




---

### thumb

Resize image

```php
FileBehavior::thumb( string $attribute, string $preset, string $pathToFile = null, boolean $returnRealPath = false ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |
| `$preset` | **string** | The name of the preset |
| `$pathToFile` | **string** | Use this path to the file |
| `$returnRealPath` | **boolean** | Return the real path to the file |




---

### createFile

Create a file

```php
FileBehavior::createFile( string $attribute, string $path, string $title, boolean $temporary ): \rkit\filemanager\behaviors\rkit\filemanager\models\File
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |
| `$path` | **string** | The path of the file |
| `$title` | **string** | The title of file |
| `$temporary` | **boolean** | The file is temporary |




---

### getFileRulesDescription

Get a description of the validation rules in as text

```php
FileBehavior::getFileRulesDescription( string $attribute ): string
```

Example

```php
$form->field($model, $attribute)->hint($model->getFileRulesDescription($attribute)
```

Output

```
Min. size of image: 300x300px
File types: JPG, JPEG, PNG
Max. file size: 1.049 MB
```


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | Attribute of a model |




---

## FileBind

The bind class for FileBehavior



* Full name: \rkit\filemanager\behaviors\FileBind


### bindMultiple

Bind files to the with owner

```php
FileBind::bindMultiple( \rkit\filemanager\behaviors\Storage $storage, integer $ownerId, integer $ownerType, array $files ): array&lt;mixed,\rkit\filemanager\behaviors\rkit\filemanager\models\File&gt;|boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$storage` | **\rkit\filemanager\behaviors\Storage** | The storage for the files |
| `$ownerId` | **integer** | The id of the owner |
| `$ownerType` | **integer** | The type of the owner |
| `$files` | **array** | Array of ids |




---

## FileManager

Component of FileManager



* Full name: \rkit\filemanager\FileManager
* Parent class:


### getOwnerType

Get owner type

```php
FileManager::getOwnerType( string $ownerType ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ownerType` | **string** | The type of the owner |




---

## FormatValidation

Formatting of validation rules



* Full name: \rkit\filemanager\helpers\FormatValidation


### getDescription

Get a description of the validation rules in as text

```php
FormatValidation::getDescription( array $rules ): string
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rules` | **array** | Validation rules |




---

## LocalStorage

The local storage for files



* Full name: \rkit\filemanager\storages\LocalStorage
* Parent class: \rkit\filemanager\Storage


### setFile

Set a file

```php
LocalStorage::setFile( \rkit\filemanager\File $file ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **\rkit\filemanager\File** | File |




---

### getFile

Get a file

```php
LocalStorage::getFile(  ): \rkit\filemanager\File
```







---

### path

Path to the file

```php
LocalStorage::path( boolean $realPath = false ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$realPath` | **boolean** | The real path of the file |




---

### save

Save the file to the storage
If the file is temporary, then in the temporary directory

```php
LocalStorage::save( string $path ): \rkit\filemanager\models\File|boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **string** | The path of the file |




---

### saveTemporaryFileToStorage

Save the temporary file to the storage

```php
LocalStorage::saveTemporaryFileToStorage(  ): boolean
```







---

### delete



```php
LocalStorage::delete(  )
```







---



--------
> This document was automatically generated from source code comments on 2016-08-23 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
