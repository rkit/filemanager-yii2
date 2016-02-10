<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
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
     * @var string $modelName The name of model
     */
    public $modelName;
    /**
     * @var string $attribute
     */
    public $attribute;
    /**
     * @var string $inputName The name of the file input field
     */
    public $inputName;
    /**
     * @var string $type The type of the file (`image` or `file`)
     */
    public $type = 'image';
    /**
     * @var string $multiple Multiple files
     */
    public $multiple = false;
    /**
     * @var string $template Path to template for multiple files
     */
    public $template;
    /**
     * @var string $resultFieldId The name for field of the response, in which the id to the file
     */
    public $resultFieldId = 'id';
    /**
     * @var string $resultFieldPath The name for field of the response, in which the path to the file
     */
    public $resultFieldPath = 'path';
    /**
     * @var int $ownerId The id of the owner
     */
    public $ownerId = -1;
    /**
     * @var bool $temporary The file is temporary
     */
    public $temporary = true;
    /**
     * @var ActiveRecord $model
     */
    private $model;
    /**
     * @see http://www.yiiframework.com/doc-2.0/guide-tutorial-core-validators.html
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
     * @param UploadedFile $file
     * @return string JSON
     */
    private function upload($file)
    {
        $file = $this->createFile($file);
        if ($file) {
            $presetAfterUpload = $this->model->getFilePresetAfterUpload($this->attribute);
            if (count($presetAfterUpload)) {
                $this->applyPreset($file->getStorage()->path(), $presetAfterUpload);
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
                return $this->response([
                    $this->resultFieldId => $file->id,
                    $this->resultFieldPath => $file->getStorage()->path()
                ]);
            }
        } else {
            return $this->response(['error' => Yii::t('filemanager-yii2', 'Error saving file')]); // @codeCoverageIgnore
        }
    }

    /**
     * Create a file
     *
     * @param yii\web\UploadedFile $file
     * @return rkit\filemanager\models\File
     */
    private function createFile($file)
    {
        $file = Yii::$app->fileManager->getDecoder()->createFromUploader(
            $this->model->getFileStorage($this->attribute),
            $file->tempName,
            $this->ownerId,
            $this->model->getFileOwnerType($this->attribute),
            $this->temporary,
            $this->model->isFileProtected($this->attribute)
        );

        return $file;
    }

    /**
     * Apply preset for file
     *
     * @param string $path
     * @param array $presetAfterUpload
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
