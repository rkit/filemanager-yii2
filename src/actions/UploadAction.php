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
use rkit\filemanager\models\File;

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
     * @var string $type Image Or File
     */
    public $type = 'image';
    /**
     * @var string $multiple
     */
    public $multiple = false;
    /**
     * @var string $template Path to template.
     */
    public $template;
    /**
     * @var string $resultName
     */
    public $resultName = 'path';
    /**
     * @var int $ownerId Owner Id
     */
    public $ownerId = null;
    /**
     * @var bool $saveAfterUpload Save the file immediately after upload
     */
    public $saveAfterUpload = false;
    /**
     * @var int $status Status a file. Unprotected or Protected.
     */
    public $status = File::STATUS_UNPROTECTED;
    /**
     * @var ActiveRecord $model
     */
    private $model;
    /**
     * @var array $rules
     */
    private $rules;
    /**
     * @var array $resizeRules
     */
    private $resizeRules;

    public function init()
    {
        if ($this->modelName === null) {
            throw new InvalidParamException('The "modelName" attribute must be set.');
        }

        $this->model = new $this->modelName();

        $this->rules = $this->model->getFileRules($this->attribute);
        $this->resizeRules = $this->model->getFileResizeRules($this->attribute);

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

    private function upload($file)
    {
        $ownerType = $this->model->getFileOwnerType($this->attribute);
        if ($this->saveAfterUpload && $this->ownerId === null) {
            $this->ownerId = 0;
        }

        $file = File::createFromUploader($file, $this->ownerId, $ownerType, $this->saveAfterUpload, $this->status);
        if ($file) {
            if (count($this->resizeRules)) {
                Yii::$app->fileManager->resize(
                    $file->path(),
                    $this->resizeRules['width'],
                    $this->resizeRules['height'],
                    $this->resizeRules['ratio'],
                    true
                );
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
     * JSON Response.
     *
     * @param mixed $data
     */
    public function response($data)
    {
        if (!Yii::$app instanceof \yii\console\Application) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON; // @codeCoverageIgnore
        }
        return $data;
    }
}
