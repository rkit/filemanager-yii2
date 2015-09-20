<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager\actions;

use Yii;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidParamException;
use yii\web\UploadedFile;

class UploadAction extends Action
{
    /**
     * @var string $modelName
     */
    public $modelName;
    /**
     * @var string $attribute
     */
    public $attribute;
    /**
     * @var string $inputName
     */
    public $inputName;
    /**
     * @var string $type `image` or `file`
     */
    public $type = 'image';
    /**
     * @var string $multiple
     */
    public $multiple = false;
    /**
     * @var string $template Path to template
     */
    public $template;
    /**
     * @var string $resultName
     */
    public $resultName = 'path';
    /**
     * @var int $ownerId Owner Id
     */
    public $ownerId = -1;
    /**
     * @var bool $saveAfterUpload Save the file immediately after upload
     */
    public $saveAfterUpload = false;
    /**
     * @var ActiveRecord $model
     */
    private $model;
    /**
     * @var array $rules
     */
    private $rules;

    public function init()
    {
        if ($this->modelName === null) {
            throw new InvalidParamException('The "modelName" attribute must be set.');
        }

        $this->model = new $this->modelName();
        $this->rules = $this->model->getFileRules($this->attribute);

        if (isset($this->rules['imageSize'])) {
            $this->rules = array_merge($this->rules, $this->rules['imageSize']);
            unset($this->rules['imageSize']);
        }
    }

    public function run()
    {
        $file = UploadedFile::getInstanceByName($this->inputName);

        if (!$file) {
            return $this->response(
                ['error' => Yii::t('filemanager-yii2', 'An error occured, try again later…')]
            );
        }

        $model = new DynamicModel(compact('file'));
        $model->addRule('file', $this->type, $this->rules)->validate();

        if ($model->hasErrors()) {
            return $this->response(['error' => $model->getFirstError('file')]);
        } else {
            return $this->upload($file);
        }
    }

    /**
     * Upload
     *
     * @param yii\web\UploadedFile $file
     * @return string JSON
     */
    private function upload($file)
    {
        $file = Yii::$app->fileManager->create(
            $file,
            $this->ownerId,
            $this->model->getFileOwnerType($this->attribute),
            $this->saveAfterUpload,
            $this->model->isProtected($this->attribute)
        );
        if ($file) {
            $presetAfterUpload = $this->model->getFilePresetAfterUpload($this->attribute);
            if (count($presetAfterUpload)) {
                $this->applyPreset($file->path(), $presetAfterUpload);
            }
            if ($this->multiple) {
                return $this->response(
                    $this->controller->renderFile($this->template, [
                        'file' => $file,
                        'model' => $this->model,
                        'attribute' => $this->attribute
                    ])
                );
            } else {
                return $this->response(['id' => $file->id, $this->resultName => $file->path()]);
            }
        } else {
            return $this->response(['error' => Yii::t('filemanager-yii2', 'Error saving file')]); // @codeCoverageIgnore
        }
    }

    /**
     * Apply preset for file
     *
     * @param array $presetAfterUpload
     * @param string $path
     * @return void
     */
    private function applyPreset($path, $presetAfterUpload)
    {
        foreach ($presetAfterUpload as $preset) {
            $this->model->thumb($this->attribute, $preset, $path);
        }
    }

    /**
     * JSON Response
     *
     * @param mixed $data
     * @return string JSON Only for yii\web\Application, for console app returns `mixed`
     */
    private function response($data)
    {
        // @codeCoverageIgnoreStart
        if (!Yii::$app instanceof \yii\console\Application) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        }
        // @codeCoverageIgnoreEnd
        return $data;
    }
}
