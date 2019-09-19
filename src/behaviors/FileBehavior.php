<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager\behaviors;

use rkit\filemanager\models\FileUploadSession;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
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
     * @var array
     */
    protected static $classPathMap = [];

    /**
     * @var string name of application component that represents `user`
     */
    public $userComponent = 'user';

    /**
     * @since 5.6.0
     * @var bool
     */
    protected $markedLinked = false;

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
        foreach ($this->attributes as $attribute => $options)
        {
            $disableAutobind = $this->fileOption($attribute, 'disableAutobind');
            if ($disableAutobind) {
                continue;
            }

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
                $this->fileBind->delete($this->owner, $attribute, $this->files($attribute));
                continue;
            }

            $maxFiles = ArrayHelper::getValue($this->fileRules($attribute, true), 'maxFiles');
            if (is_array($files) && $maxFiles !== null) {
                $files = array_slice($files, 0, $maxFiles, true);
            }

            $files = $this->fileBind->bind($this->owner, $attribute, $files);

            $this->clearState($attribute, $files);

            if (is_array($files)) {
                $files = array_shift($files);
                $this->setValue($attribute, $files, $options['oldValue']);
            }
        }
    }

    /**
     * @internal
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function beforeDelete()
    {
        foreach ($this->attributes as $attribute => $options) {
            $disableAutobind = $this->fileOption($attribute, 'disableAutobind');
            if (!$disableAutobind) {
                $this->fileBind->delete($this->owner, $attribute, $this->files($attribute));
            }
        }
    }

    protected function getUser()
    {
        if (!$this->userComponent || !isset(Yii::$app->{$this->userComponent})) {
            return false;
        }
        return Yii::$app->{$this->userComponent};
    }

    public function clearState($attribute, $files)
    {
        if (!$this->getUser()) {
            return [];
        }
        if (!is_array($files)) {
            $files = [$files];
        }
        $query = [
            'created_user_id' => $this->getUser()->id,
            'target_model_class' => static::getClass(get_class($this->owner)),
            'target_model_id' => $this->owner->getPrimaryKey(),
            'target_model_attribute' => $attribute,
        ];
        if ($files) {
            $fileIDs = ArrayHelper::getColumn($files, 'id');
            $query['file_id'] = $fileIDs;
        }
        FileUploadSession::deleteAll($query);
        $query['target_model_id'] = null;
        FileUploadSession::deleteAll($query);  // for cases of uploads when original model was a new record at the moment of uploads
        return;
    }

    private function setState($attribute, $file)
    {
        $rec = new FileUploadSession();
        $rec->created_user_id = $this->getUser()->id;
        $rec->file_id = $file->getPrimaryKey();
        $rec->target_model_attribute = $attribute; // TODO: write model/object id?
        $rec->target_model_id = (!$this->owner->isNewRecord ? $this->owner->getPrimaryKey() : null);
        $rec->target_model_class = static::getClass(get_class($this->owner));
        $rec->save(false);
    }

    /**
     * for models with single upload only
     * @param $attribute
     * @param $file
     * @param $defaultValue
     */
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
        if ($this->fileOption($attribute, 'disableAutobind')) {
            return [];
        }
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
    public function files($attribute)
    {
        if ($this->fileOption($attribute, 'disableAutobind')) {
            throw new Exception('Accessing `files()` is not allowed when auto-bind is disabled, see `FileBehavior::$disableAutobind`');
        }
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
        if ($this->fileOption($attribute, 'disableAutobind')) {
            throw new Exception('Accessing `file()` is not allowed when auto-bind is disabled, see `FileBehavior::$disableAutobind`');
        }
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
        if (!$this->getUser()) {
            return [];
        }
        $query = FileUploadSession::find()->where([
            'created_user_id' => $this->getUser()->id,
            'target_model_class' => static::getClass(get_class($this->owner)),
            'target_model_attribute' => $attribute,
        ]);
        $query->andWhere(['or',
            ['target_model_id' => $this->owner->getPrimaryKey()],
            ['target_model_id' => null] // for cases of uploads when original model was a new record at the moment of uploads
        ]);
        $data = $query->all();
        if ($data) {
            return ArrayHelper::getColumn($data, ['file_id']);
        } else {
            return [];
        }
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
            if ($storage->write($handlerTemplatePath($file), $contents, [
                // set correct mime type:
                'mimetype' => yii\helpers\FileHelper::getMimeTypeByExtension($name),
            ])) {
                $disableAutobind = $this->fileOption($attribute, 'disableAutobind');
                if (!$this->markedLinked && !$disableAutobind) {
                    $this->setState($attribute, $file);
                }
                $this->owner->{$attribute} = $file->id;
                return $file;
            }
        } // @codeCoverageIgnore
        return false; // @codeCoverageIgnore
    }

    /**
     * Create a file from remote URL
     *
     * @author Sergii Gamaiunov <devkadabra@gmail.com>
     *
     * @param string $attribute The attribute name
     * @param \igogo5yo\uploadfromurl\UploadFromUrl $remoteFile
     * @param string $name The file name
     * @return \ActiveRecord The file model
     */
    public function createRemoteFile($attribute, $remoteFile, $name)
    {
        $url = $remoteFile->url;
        $handlerCreateFile = $this->fileOption($attribute, 'createRemoteFile');
        $file = $handlerCreateFile($remoteFile, $name);
        if ($file) {
            $storage = $this->fileStorage($attribute);
            $stream = fopen($url, 'r');
            $handlerTemplatePath = $this->fileOption($attribute, 'templatePath');
            if ($storage->putStream($handlerTemplatePath($file), $stream)) {
                if (is_resource($stream)) { // some adapters close resources on their own
                    fclose($stream);
                }
                if ($this->getUser()) {
                    if (!$this->markedLinked) {
                        $this->setState($attribute, $file);
                    }
                }
                $this->owner->{$attribute} = $file->id;
                return $file;
            }
        } // @codeCoverageIgnore
        return false; // @codeCoverageIgnore
    }

    /**
     * Add class alias to be able to upload files for different versions of a model to a single API endpoint
     *
     * Example:
     * ```
     * class OldCar extends Car
     * {
     *      public function init()
     *      {
     *          parent::init();
     *          $this->car_type = 'old;
     *          FileBehavior::addClassAlias(get_class($this), Car::className());
     *      }
     *
     *      public function formName() {
     *          return 'Car';
     *      }
     * }
     * ```
     * @param $source
     * @param $mapTo
     */
    public static function addClassAlias($source, $mapTo) {
        static::$classPathMap[$source] = $mapTo;
    }

    protected static function getClass($source) {
        return isset(static::$classPathMap[$source])
            ? static::$classPathMap[$source]
            : $source;
    }

    /**
     * Mark current upload session as already linked (e.g. file is linked during `createFile`) to avoid duplicate links
     * @return $this
     * @since 5.6.0
     */
    public function markLinked() {
        $this->markedLinked = true;
        return $this;
    }
}
