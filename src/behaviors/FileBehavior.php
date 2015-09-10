<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use rkit\filemanager\models\File;

class FileBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];

    public function init()
    {
        parent::init();
        Yii::$app->fileManager->registerTranslations();
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

            $ownerType = Yii::$app->fileManager->getOwnerType($this->getOwnerType($attribute));
            $file = $this->bind($this->owner->primaryKey, $ownerType, $fileId);

            if (isset($data['saveFilePath']) && $data['saveFilePath'] === true) {
                $this->owner->updateAttributes([$attribute => $this->prepareFilePath($file, $data['oldValue'])]);
            } elseif (isset($data['saveFileId']) && $data['saveFileId'] === true) {
                $this->owner->updateAttributes([$attribute => $this->prepareFileId($file, $data['oldValue'])]);
            }
        }
    }

    public function beforeDelete()
    {
        foreach ($this->attributes as $attribute => $data) {
            $ownerType = Yii::$app->fileManager->getOwnerType($this->getOwnerType($attribute));
            File::deleteByOwner($this->owner->primaryKey, $ownerType);
        }
    }

    /**
     * Get owner type
     *
     * @param string $attribute
     * @return string
     */
    public function getOwnerType($attribute)
    {
        return $this->owner->tableName() . '.' . $attribute;
    }

    /**
     * Binding files with owner
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param array|int $fileId
     * @return File|bool|array
     */
    public function bind($ownerId, $ownerType, $fileId)
    {
        if ($fileId === [] || $fileId === '') {
            File::deleteByOwner($ownerId, $ownerType);
            return true;
        }

        return is_array($fileId)
            ? $this->bindMultiple($ownerId, $ownerType, $fileId)
            : $this->bindSingle($ownerId, $ownerType, $fileId);
    }

    /**
     * Binding file with owner
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param int $fileId
     * @return File|bool
     */
    private function bindSingle($ownerId, $ownerType, $fileId)
    {
        $file = $fileId ? File::findOne($fileId) : false;

        if ($file && $file->isOwner($ownerId, $ownerType)) {
            if ($this->bindSingleFile($file, $ownerId)) {
                // delete unnecessary files
                $currentFiles = File::getByOwner($ownerId, $ownerType);
                foreach ($currentFiles as $currFile) {
                    if ($currFile->id !== $file->id) {
                        $currFile->delete();
                    }
                }

                return $file;
            }
        }

        return false;
    }

    /**
     * Bind single file
     *
     * @param File $file
     * @param int $ownerId
     * @return bool
     */
    private function bindSingleFile($file, $ownerId)
    {
        if ($file->tmp) {
            $file->owner_id = $ownerId;
            $file->tmp = false;
            if ($file->saveFile()) {
                $file->updateAttributes(['tmp' => $file->tmp, 'owner_id' => $file->owner_id]);
                return true;
            }
        }

        return false;
    }

    /**
     * Binding files with owner
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param array $files
     * @return array|bool
     */
    private function bindMultiple($ownerId, $ownerType, $files)
    {
        $files = $this->bindMultiplePrepare($files);
        $newFiles = ArrayHelper::index(File::findAll(array_keys($files)), 'id');
        $currentFiles = ArrayHelper::index(File::getByOwner($ownerId, $ownerType), 'id');

        if (count($newFiles)) {
            foreach ($newFiles as $file) {
                if (!$file->isOwner($ownerId, $ownerType)) {
                    unset($newFiles[$file->id]);
                    continue;
                }
                if (!$this->bindMultipleFile($file, $ownerId, $files)) {
                    return false;
                }
            }

            // delete unnecessary files
            foreach ($currentFiles as $currFile) {
                if (!array_key_exists($currFile->id, $newFiles)) {
                    $currFile->delete();
                }
            }

        } else {
            // if empty array — delete current files
            foreach ($currentFiles as $currFile) {
                $currFile->delete();
            }
        }

        return $newFiles;
    }

    /**
     * Prepare files for bind
     *
     * @param array $files
     * @return array
     */
    private function bindMultiplePrepare($files)
    {
        $files = array_filter($files);
        $files = array_combine(array_map(function ($a) {
            return substr($a, 2);
        }, array_keys($files)), $files);

        return $files;
    }

    /**
     * Bind files
     *
     * @param File $file
     * @param int $ownerId
     * @param array $files See `bindMultiple`
     * @return bool
     */
    private function bindMultipleFile($file, $ownerId, $files)
    {
        if ($file->tmp) {
            $file->owner_id = $ownerId;
            $file->tmp = false;
            if ($file->saveFile()) {
                $file->updateAttributes([
                    'tmp'      => $file->tmp,
                    'owner_id' => $file->owner_id,
                    'title'    => @$files[$file->id],
                    'position' => @array_search($file->id, array_keys($files)) + 1
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare file path
     *
     * @param mixed $file
     * @param mixed $oldValue
     * @return string
     */
    private function prepareFilePath($file, $oldValue)
    {
        if (is_object($file)) {
            return $file->path();
        } elseif ($file === false && $oldValue !== null) {
            return $oldValue;
        } else {
            return '';
        }
    }

    /**
     * Prepare file id
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
     * Get ownerType
     *
     * @param string $attribute
     * @return int
     */
    public function getFileOwnerType($attribute)
    {
        return Yii::$app->fileManager->getOwnerType($this->getOwnerType($attribute));
    }

    /**
     * Get files
     *
     * @param string $attribute
     * @return array
     */
    public function getFiles($attribute)
    {
        return File::getByOwner($this->owner->primaryKey, $this->getFileOwnerType($attribute));
    }

    /**
     * Get file status (protected or unprotected)
     *
     * @param string $attribute
     * @return int
     */
    public function getFileStatus($attribute)
    {
        return ArrayHelper::getValue($this->attributes[$attribute], 'protected', false);
    }

    /**
     * Get real path to file
     *
     * @param string $attribute
     * @return string
     */
    public function getRealPath($attribute)
    {
        if ($this->getFileStatus($attribute) === File::STATUS_PROTECTED) {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirProtected);
        } else {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected);
        }
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
     * Get file preset
     *
     * @param string $attribute
     * @return array
     */
    public function getFilePreset($attribute)
    {
        return array_keys(ArrayHelper::getValue($this->attributes[$attribute], 'preset', []));
    }

    /**
     * Get preset file after upload
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
     * Resize image
     *
     * @param string $attribute
     * @param string $preset
     * @param string $forcePublicPath Use this path
     * @param bool $returnRealPath
     * @return string
     */
    public function thumb($attribute, $preset, $forcePublicPath = null, $returnRealPath = false)
    {
        $realPath = $this->getRealPath($attribute);
        $publicPath = $forcePublicPath ? $forcePublicPath : $this->owner->$attribute;
        $fileName = pathinfo($publicPath, PATHINFO_FILENAME);
        $thumbPath = str_replace($fileName, $preset . '_' . $fileName, $publicPath);

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
     * Get rules description
     *
     * @param string $attribute
     * @return string
     */
    public function getFileRulesDescription($attribute)
    {
        $rules = $this->attributes[$attribute]['rules'];

        $text = '';
        if (isset($rules['imageSize'])) {
            $text .= $this->prepareImageSizeDescription($rules['imageSize']);
            $text = !empty($text) ? $text . '<br>' : $text;
        }

        if (isset($rules['extensions'])) {
            $text .= $this->prepareExtensionDescription($rules['extensions']);
            $text = isset($rules['maxSize']) ? $text . '<br>' : $text;
        }

        if (isset($rules['maxSize'])) {
            $text .= $this->prepareMaxSizeDescription($rules['maxSize']);
        }

        return $text;
    }

    /**
     * Prepare description for max size of file
     *
     * @param int $rules
     * @return string
     */
    private function prepareMaxSizeDescription($rules)
    {
        $maxSize = Yii::$app->formatter->asShortSize($rules);
        return Yii::t('filemanager-yii2', 'Max. file size') . ': ' . $maxSize . ' ';
    }

    /**
     * Prepare description for extensions of file
     *
     * @param array $rules
     * @return string
     */
    private function prepareExtensionDescription($rules)
    {
        $extensions = strtoupper(implode(', ', $rules));
        return Yii::t('filemanager-yii2', 'File types') . ': ' . $extensions . ' ';
    }

    /**
     * Prepare description for size of image
     *
     * @param array $rules
     * @return string
     */
    private function prepareImageSizeDescription($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        $text = '';
        if ($this->imageWithStrictSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Image size') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif ($this->imageWithMinAndMaxSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= Yii::t('filemanager-yii2', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif ($this->imageWithMinSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= $this->prepareImageFullSizeDescription($rules, ['minWidth', 'minHeight']);
        } elseif ($this->imageWithMaxSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
            $text .= $this->prepareImageFullSizeDescription($rules, ['maxWidth', 'maxHeight']);
        } else {
            $text .= $this->prepareImageFullSizeDescription($rules);
        }

        $text = mb_substr($text, 0, -1);
        $text = str_replace(';', '<br>', $text);

        return $text;
    }

    /**
     * Prepare description for strict size of image
     *
     * @param array $rules
     * @return string
     */
    private function imageWithStrictSize($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        return count($rules) == 4 && ($maxWidth == $minWidth && $maxHeight == $minHeight);
    }

    /**
     * Prepare description for min-max size of image
     *
     * @param array $rules
     * @return string
     */
    private function imageWithMinAndMaxSize($rules)
    {
        return count($rules) == 4;
    }

    /**
     * Prepare description for min size of image
     *
     * @param array $rules
     * @return string
     */
    private function imageWithMinSize($rules)
    {
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        return (count($rules) == 2 || count($rules) == 3) && $minWidth && $minHeight;
    }

    /**
     * Prepare description for max size of image
     *
     * @param array $rules
     * @return string
     */
    private function imageWithMaxSize($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');

        return (count($rules) == 2 || count($rules) == 3) && $maxWidth && $maxHeight;
    }

    /**
     * Prepare description for full size of image
     *
     * @param array $rules
     * @return string
     */
    private function prepareImageFullSizeDescription($rules, $exclude = [])
    {
        foreach ($exclude as $item) {
            unset($rules[$item]);
        }

        $text = '';
        foreach ($rules as $rule => $value) {
            switch ($rule) {
                case 'minWidth':
                    $text .= Yii::t('filemanager-yii2', 'Min. width') . ' ' . $value . 'px;';
                    break;
                case 'minHeight':
                    $text .= Yii::t('filemanager-yii2', 'Min. height') . ' ' . $value . 'px;';
                    break;
                case 'maxWidth':
                    $text .= Yii::t('filemanager-yii2', 'Max. width') . ' ' . $value . 'px;';
                    break;
                case 'maxHeight':
                    $text .= Yii::t('filemanager-yii2', 'Max. height') . ' ' . $value . 'px;';
                    break;
            }
        }

        return $text;
    }
}
