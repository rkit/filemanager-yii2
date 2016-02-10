<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\base\InvalidParamException;
use rkit\filemanager\models\File;
use rkit\filemanager\helpers\FileRules;
use rkit\filemanager\behaviors\FileBind;

class FileBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];
    /**
     * @var rkit\filemanager\behaviors\FileBind
     */
    private static $bind;

    public function init()
    {
        parent::init();

        $this->setBind();
        Yii::$app->fileManager->registerTranslations();
    }

    /**
     * Set Decoder
     *
     * @return void
     */
    public function setBind()
    {
        $this->bind = new FileBind();
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT  => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE  => 'afterSave',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    public function beforeSave($insert)
    {
        foreach ($this->attributes as $attribute => $data) {
            $oldValue = $this->owner->isNewRecord ? null : $this->owner->getOldAttribute($attribute);
            $isAttributeChanged = $oldValue === null ? true : $this->owner->isAttributeChanged($attribute);

            $this->attributes[$attribute]['isAttributeChanged'] = $isAttributeChanged;
            $this->attributes[$attribute]['oldValue'] = $oldValue;
        }
    }

    public function afterSave()
    {
        foreach ($this->attributes as $attribute => $data) {
            $fileId = $this->owner->{$attribute};

            if ($data['isAttributeChanged'] === false || $fileId === null) {
                continue;
            }

            $file = $this->bind->setBind(
                $this->getFileStorage($attribute),
                $this->owner->primaryKey,
                $this->getFileOwnerType($attribute),
                $fileId
            );

            if (isset($data['saveFilePath']) && $data['saveFilePath'] === true) {
                $value = $this->prepareFilePath($file, $data['oldValue']);
            } elseif (isset($data['saveFileId']) && $data['saveFileId'] === true) {
                $value = $this->prepareFileId($file, $data['oldValue']);
            }

            if (isset($value)) {
                $this->owner->updateAttributes([$attribute => $value]);
            }
        }
    }

    public function beforeDelete()
    {
        foreach ($this->attributes as $attribute => $data) {
            $ownerType = $this->getFileOwnerType($attribute);
            $storage = $this->getFileStorage($attribute);
            (new File())->deleteByOwner($storage, $this->owner->primaryKey, $ownerType);
        }
    }

    /**
     * Prepare the path of the file
     *
     * @param mixed $file
     * @param mixed $oldValue
     * @return string
     */
    private function prepareFilePath($file, $oldValue)
    {
        if (is_object($file)) {
            return $file->getStorage()->path();
        } elseif ($file === false && $oldValue !== null) {
            return $oldValue;
        } else {
            return '';
        }
    }

    /**
     * Prepare the id of the file
     *
     * @param mixed $file
     * @param mixed $oldValue
     * @return int
     */
    private function prepareFileId($file, $oldValue)
    {
        if (is_object($file)) {
            return $file->id;
        } elseif ($file === false && $oldValue !== null) {
            return $oldValue;
        } else {
            return 0;
        }
    }

    /**
     * Get the path to the upload directory
     *
     * @param string $attribute
     * @return string
     */
    public function uploadDir($attribute)
    {
        if ($this->isFileProtected($attribute)) {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirProtected);
        } else {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected);
        }
    }

    /**
     * Get the type of the owner in as string
     *
     * @param string $attribute
     * @return string
     */
    private function getStringOwnerType($attribute)
    {
        return $this->owner->tableName() . '.' . $attribute;
    }

    /**
     * Get the type of the owner
     *
     * @param string $attribute
     * @return int
     */
    public function getFileOwnerType($attribute)
    {
        return Yii::$app->fileManager->getOwnerType($this->getStringOwnerType($attribute));
    }

    /**
     * Get files
     *
     * @param string $attribute
     * @return array
     */
    public function getFiles($attribute)
    {
        $files = File::findAllByOwner($this->owner->primaryKey, $this->getFileOwnerType($attribute));
        foreach ($files as $file) {
            $file->setStorage($this->getFileStorage($attribute));
        }

        return $files;
    }

    /**
     * Get the file
     *
     * @param string $attribute
     * @return File|null
     */
    public function getFile($attribute)
    {
        $file = File::findOneByOwner($this->owner->primaryKey, $this->getFileOwnerType($attribute));
        $file->setStorage($this->getFileStorage($attribute));
        return $file;
    }

    /**
     * The file is protected
     *
     * @param string $attribute
     * @return bool
     */
    public function isFileProtected($attribute)
    {
        return ArrayHelper::getValue($this->attributes[$attribute], 'protected', false);
    }

    /**
     * Get rules
     *
     * @param string $attribute
     * @return array
     */
    public function getFileRules($attribute)
    {
        return ArrayHelper::getValue($this->attributes[$attribute], 'rules', []);
    }

    /**
     * Get the presets of the file
     *
     * @param string $attribute
     * @return array
     */
    public function getFilePreset($attribute)
    {
        return array_keys(ArrayHelper::getValue($this->attributes[$attribute], 'preset', []));
    }

    /**
     * Get the presets of the file for apply after upload
     *
     * @param string $attribute
     * @return array
     */
    public function getFilePresetAfterUpload($attribute)
    {
        $preset = ArrayHelper::getValue($this->attributes[$attribute], 'applyPresetAfterUpload', false);
        if (is_string($preset) && $preset === '*') {
            return $this->getFilePreset($attribute);
        } elseif (is_array($preset)) {
            return $preset;
        }

        return [];
    }

    /**
     * Get the storage of the file
     *
     * @param string $attribute
     * @return Storage
     */
    public function getFileStorage($attribute)
    {
        $storage = ArrayHelper::getValue($this->attributes[$attribute], 'storage', null);
        if ($storage) {
            return new $storage();
        }

        throw new InvalidParamException('The storage is not defined'); // @codeCoverageIgnore
    }

    /**
     * Generate a thumb name
     *
     * @param string $path
     * @param string $prefix
     * @return string
     */
    public function generateThumbName($path, $prefix)
    {
        $fileName = pathinfo($path, PATHINFO_FILENAME);
        return str_replace($fileName, $prefix . '_' . $fileName, $path);
    }

    /**
     * Resize image
     *
     * @param string $attribute
     * @param string $preset
     * @param string $pathToFile Use this path to the file
     * @param bool $returnRealPath Return the real path to the file
     * @return string
     */
    public function thumb($attribute, $preset, $pathToFile = null, $returnRealPath = false)
    {
        $realPath = $this->uploadDir($attribute);
        $publicPath = $pathToFile ? $pathToFile : $this->owner->$attribute;
        $thumbPath = $this->generateThumbName($publicPath, $preset);

        if (!file_exists($realPath . $thumbPath)) {
            if (file_exists($realPath . $publicPath)) {
                $thumbInit = ArrayHelper::getValue($this->attributes[$attribute]['preset'], $preset);
                if ($thumbInit) {
                    $thumbInit($realPath, $publicPath, $thumbPath);
                }
            }
        }

        return $returnRealPath ? $realPath . $thumbPath : $thumbPath;
    }

    /**
     * Get description the rules of the file in as text
     *
     * @param string $attribute
     * @return string
     */
    public function getFileRulesDescription($attribute)
    {
        return FileRules::getDescription($this->attributes[$attribute]['rules']);
    }
}
