# FileManager for Yii2

## Table of Contents

* [FileBehavior](#filebehavior)
    * [fileRelation](#filerelation)
    * [fileOption](#fileoption)
    * [fileStorage](#filestorage)
    * [filePath](#filepath)
    * [fileUrl](#fileurl)
    * [fileExtraFields](#fileextrafields)
    * [allFiles](#allfiles)
    * [file](#file)
    * [fileRules](#filerules)
    * [fileState](#filestate)
    * [filePresetAfterUpload](#filepresetafterupload)
    * [thumbUrl](#thumburl)
    * [thumbPath](#thumbpath)
    * [createFile](#createfile)

## FileBehavior





* Full name: \rkit\filemanager\behaviors\FileBehavior
* Parent class: 


### fileRelation

Get relation

```php
FileBehavior::fileRelation( string $attribute ): \ActiveQuery
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |




---

### fileOption

Get file option

```php
FileBehavior::fileOption( string $attribute, string $option, mixed $defaultValue = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |
| `$option` | **string** | Option name |
| `$defaultValue` | **mixed** | Default value |




---

### fileStorage

Get file storage

```php
FileBehavior::fileStorage( string $attribute ): \Flysystem
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |




---

### filePath

Get file path

```php
FileBehavior::filePath( string $attribute, \yii\db\ActiveRecord $file = null ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |
| `$file` | **\yii\db\ActiveRecord** | Use this file model |


**Return Value:**

The file path



---

### fileUrl

Get file url

```php
FileBehavior::fileUrl( string $attribute, \yii\db\ActiveRecord $file = null ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |
| `$file` | **\yii\db\ActiveRecord** | Use this file model |


**Return Value:**

The file url



---

### fileExtraFields

Get extra fields of file

```php
FileBehavior::fileExtraFields( string $attribute ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |




---

### allFiles

Get files

```php
FileBehavior::allFiles( string $attribute ): array&lt;mixed,\ActiveRecord&gt;
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |


**Return Value:**

The file models



---

### file

Get the file

```php
FileBehavior::file( string $attribute ): \ActiveRecord
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |


**Return Value:**

The file model



---

### fileRules

Get rules

```php
FileBehavior::fileRules( string $attribute, boolean $onlyCoreValidators = false ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |
| `$onlyCoreValidators` | **boolean** | Only core validators |




---

### fileState

Get file state

```php
FileBehavior::fileState( string $attribute ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |




---

### filePresetAfterUpload

Get the presets of the file for apply after upload

```php
FileBehavior::filePresetAfterUpload( string $attribute ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |




---

### thumbUrl

Create a thumb and return url

```php
FileBehavior::thumbUrl( string $attribute, string $preset, \yii\db\ActiveRecord $file = null ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |
| `$preset` | **string** | The preset name |
| `$file` | **\yii\db\ActiveRecord** | Use this file model |


**Return Value:**

The file url



---

### thumbPath

Create a thumb and return full path

```php
FileBehavior::thumbPath( string $attribute, string $preset, \yii\db\ActiveRecord $file = null ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |
| `$preset` | **string** | The preset name |
| `$file` | **\yii\db\ActiveRecord** | Use this file model |


**Return Value:**

The file path



---

### createFile

Create a file

```php
FileBehavior::createFile( string $attribute, string $path, string $name ): \ActiveRecord
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attribute` | **string** | The attribute name |
| `$path` | **string** | The file path |
| `$name` | **string** | The file name |


**Return Value:**

The file model



---



--------
> This document was automatically generated from source code comments on 2016-11-03 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
