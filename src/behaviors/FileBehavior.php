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

class FileBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];
    /**
     * @var ActiveQuery
     */
    private $relation;
    /**
     * @var FileBind
     */
    private $fileBind;

    /**
     * @internal
     */
    public function init()
    {
        parent::init();

        $this->fileBind = new FileBind();

        Yii::$app->fileManager->registerTranslations();
    }

    /**
     * @inheritdoc
     * @internal
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

    /**
     * @internal
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function beforeSave($insert)
    {
        foreach ($this->attributes as $attribute => $options) {
            $oldValue = $this->owner->isNewRecord ? null : $this->owner->getOldAttribute($attribute);
            $isAttributeChanged = $oldValue === null ? true : $this->owner->isAttributeChanged($attribute);

            $this->attributes[$attribute]['isAttributeChanged'] = $isAttributeChanged;
            $this->attributes[$attribute]['oldValue'] = $oldValue;
        }
    }

    /**
     * @internal
     */
    public function afterSave()
    {
        foreach ($this->attributes as $attribute => $options) {
            $files = $this->owner->{$attribute};

            $isAttributeNotChanged = $options['isAttributeChanged'] === false || $files === null;
            if ($isAttributeNotChanged) {
                continue;
            }

            if (is_numeric($files)) {
                $files = [$files];
            }

            if (is_array($files)) {
                $files = array_filter($files);
            }

            if ($files === [] || $files === '') {
                $this->fileBind->delete($this->owner, $attribute, $this->allFiles($attribute));
                continue;
            }

            $maxFiles = ArrayHelper::getValue($this->fileRules($attribute, true), 'maxFiles');
            if (is_array($files) && $maxFiles !== null) {
                $files = array_slice($files, 0, $maxFiles, true);
            }

            $files = $this->fileBind->bind($this->owner, $attribute, $files);
            if (is_array($files)) {
                $files = array_shift($files);
            }

            $this->clearState($attribute);
            $this->setValue($attribute, $files, $options['oldValue']);
        }
    }

    /**
     * @internal
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function beforeDelete()
    {
        foreach ($this->attributes as $attribute => $options) {
            $this->fileBind->delete($this->owner, $attribute, $this->allFiles($attribute));
        }
    }

    private function clearState($attribute)
    {
        $state = Yii::$app->session->get(Yii::$app->fileManager->sessionName);
        unset($state[$attribute]);
        Yii::$app->session->set(Yii::$app->fileManager->sessionName, $state);
    }

    private function setState($attribute, $file)
    {
        $state = Yii::$app->session->get(Yii::$app->fileManager->sessionName);
        if (!is_array($state)) {
            $state = [];
        }
        $state[$attribute][] = $file->getPrimaryKey();
        Yii::$app->session->set(Yii::$app->fileManager->sessionName, $state);
    }

    private function setValue($attribute, $file, $defaultValue)
    {
        $saveFilePath = $this->fileOption($attribute, 'saveFilePathInAttribute');
        $saveFileId = $this->fileOption($attribute, 'saveFileIdInAttribute');

        if ($saveFilePath || $saveFileId) {
            if (!$file) {
                $value = $defaultValue;
            } elseif ($saveFilePath) {
                $handlerTemplatePath = $this->fileOption($attribute, 'templatePath');
                $value = Yii::getAlias($this->fileOption($attribute, 'baseUrl')) . $handlerTemplatePath($file);
            } elseif ($saveFileId) {
                $value = $file->getPrimaryKey();
            }
            $this->owner->{$attribute} = $value;
            $this->owner->updateAttributes([$attribute => $value]);
        }
    }

    /**
     * Generate a thumb
     *
     * @param string $attribute The attribute name
     * @param string $preset The preset name
     * @param string $path The file path
     * @return string The thumb path
     */
    private function generateThumb($attribute, $preset, $path)
    {
        $thumbPath = pathinfo($path, PATHINFO_FILENAME);
        $thumbPath = str_replace($thumbPath, $preset . '_' . $thumbPath, $path);
        $realPath = $this->fileStorage($attribute)->path;

        if (!file_exists($realPath . $thumbPath) && file_exists($realPath . $path)) {
            $handlerPreset = $this->fileOption($attribute, 'preset.'.$preset);
            $handlerPreset($realPath, $path, $thumbPath);
        }

        return $thumbPath;
    }

    /**
     * Generate file path by template
     *
     * @param string $attribute The attribute name
     * @param ActiveRecord $file The file model
     * @return string The file path
     */
    private function templatePath($attribute, $file = null)
    {
        $value = $this->owner->{$attribute};

        $saveFilePath = $this->fileOption($attribute, 'saveFilePathInAttribute');
        $isFilledPath = $saveFilePath && !empty($value);

        $saveFileId = $this->fileOption($attribute, 'saveFileIdInAttribute');
        $isFilledId = $saveFileId && is_numeric($value) && $value;

        if (($isFilledPath || $isFilledId) && $file === null) {
            $file = $this->file($attribute);
        }

        if ($file !== null) {
            $handlerTemplatePath = $this->fileOption($attribute, 'templatePath');
            return $handlerTemplatePath($file);
        }
        return $value;
    }

    /**
     * Get relation
     *
     * @param string $attribute The attribute name
     * @return \ActiveQuery
     */
    public function fileRelation($attribute)
    {
        if ($this->relation === null) {
            $this->relation = $this->owner->getRelation($this->fileOption($attribute, 'relation'));
        }
        return $this->relation;
    }

    /**
     * Get file option
     *
     * @param string $attribute The attribute name
     * @param string $option Option name
     * @param mixed $defaultValue Default value
     * @return mixed
     */
    public function fileOption($attribute, $option, $defaultValue = null)
    {
        return ArrayHelper::getValue($this->attributes[$attribute], $option, $defaultValue);
    }

    /**
     * Get file storage
     *
     * @param string $attribute The attribute name
     * @return \Flysystem
     */
    public function fileStorage($attribute)
    {
        return Yii::$app->get($this->fileOption($attribute, 'storage'));
    }

    /**
     * Get file path
     *
     * @param string $attribute The attribute name
     * @param ActiveRecord $file Use this file model
     * @return string The file path
     */
    public function filePath($attribute, $file = null)
    {
        $path = $this->templatePath($attribute, $file);
        return $this->fileStorage($attribute)->path . $path;
    }

    /**
     * Get file url
     *
     * @param string $attribute The attribute name
     * @param ActiveRecord $file Use this file model
     * @return string The file url
     */
    public function fileUrl($attribute, $file = null)
    {
        $path = $this->templatePath($attribute, $file);
        return Yii::getAlias($this->fileOption($attribute, 'baseUrl')) . $path;
    }

    /**
     * Get extra fields of file
     *
     * @param string $attribute The attribute name
     * @return array
     */
    public function fileExtraFields($attribute)
    {
        $fields = $this->fileBind->relations($this->owner, $attribute);
        if (!$this->fileOption($attribute, 'multiple')) {
            return array_shift($fields);
        }
        return $fields;
    }

    /**
     * Get files
     *
     * @param string $attribute The attribute name
     * @return \ActiveRecord[] The file models
     */
    public function allFiles($attribute)
    {
        return $this->fileBind->files($this->owner, $attribute);
    }

    /**
     * Get the file
     *
     * @param string $attribute The attribute name
     * @return \ActiveRecord The file model
     */
    public function file($attribute)
    {
        return $this->fileBind->file($this->owner, $attribute);
    }

    /**
     * Get rules
     *
     * @param string $attribute The attribute name
     * @param bool $onlyCoreValidators Only core validators
     * @return array
     */
    public function fileRules($attribute, $onlyCoreValidators = false)
    {
        $rules = $this->fileOption($attribute, 'rules', []);
        if ($onlyCoreValidators && isset($rules['imageSize'])) {
            $rules = array_merge($rules, $rules['imageSize']);
            unset($rules['imageSize']);
        }
        return $rules;
    }

    /**
     * Get file state
     *
     * @param string $attribute The attribute name
     * @return array
     */
    public function fileState($attribute)
    {
        $state = Yii::$app->session->get(Yii::$app->fileManager->sessionName);
        return ArrayHelper::getValue($state === null ? [] : $state, $attribute, []);
    }

    /**
     * Get the presets of the file for apply after upload
     *
     * @param string $attribute The attribute name
     * @return array
     */
    public function filePresetAfterUpload($attribute)
    {
        $preset = $this->fileOption($attribute, 'applyPresetAfterUpload', []);
        if (is_string($preset) && $preset === '*') {
            return array_keys($this->fileOption($attribute, 'preset', []));
        }
        return $preset;
    }

    /**
     * Create a thumb and return url
     *
     * @param string $attribute The attribute name
     * @param string $preset The preset name
     * @param ActiveRecord $file Use this file model
     * @return string The file url
     */
    public function thumbUrl($attribute, $preset, $file = null)
    {
        $path = $this->templatePath($attribute, $file);
        $thumbPath = $this->generateThumb($attribute, $preset, $path);

        return Yii::getAlias($this->fileOption($attribute, 'baseUrl')) . $thumbPath;
    }

    /**
     * Create a thumb and return full path
     *
     * @param string $attribute The attribute name
     * @param string $preset The preset name
     * @param ActiveRecord $file Use this file model
     * @return string The file path
     */
    public function thumbPath($attribute, $preset, $file = null)
    {
        $path = $this->templatePath($attribute, $file);
        $thumbPath = $this->generateThumb($attribute, $preset, $path);

        return $this->fileStorage($attribute)->path . $thumbPath;
    }

    /**
     * Create a file
     *
     * @param string $attribute The attribute name
     * @param string $path The file path
     * @param string $name The file name
     * @return \ActiveRecord The file model
     */
    public function createFile($attribute, $path, $name)
    {
        $handlerCreateFile = $this->fileOption($attribute, 'createFile');
        $file = $handlerCreateFile($path, $name);
        if ($file) {
            $storage = $this->fileStorage($attribute);
            $contents = file_get_contents($path);
            $handlerTemplatePath = $this->fileOption($attribute, 'templatePath');
            if ($storage->write($handlerTemplatePath($file), $contents)) {
                $this->setState($attribute, $file);
                $this->owner->{$attribute} = $file->id;
                return $file;
            }
        } // @codeCoverageIgnore
        return false; // @codeCoverageIgnore
    }
}
