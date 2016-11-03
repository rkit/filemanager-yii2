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
use yii\helpers\ArrayHelper;

class UploadAction extends Action
{
    /**
     * @var string $modelClass Class name of the model
     */
    public $modelClass;
    /**
     * @var string $modelObject Model
     */
    public $modelObject;
    /**
     * @var string $attribute Attribute name of the model
     */
    public $attribute;
    /**
     * @var string $inputName The name of the file input field
     */
    public $inputName;
    /**
     * @var string $resultFieldId The name of the field that contains the id of the file in the response
     */
    public $resultFieldId = 'id';
    /**
     * @var string $resultFieldPath The name of the field that contains the path of the file in the response
     */
    public $resultFieldPath = 'path';
    /**
     * @var ActiveRecord $model
     */
    private $model;

    public function init()
    {
        if ($this->modelClass === null && $this->modelObject === null) {
            throw new InvalidParamException(
                get_class($this) . '::$modelClass or ' .get_class($this) . '::$modelObject must be set'
            );
        }

        $this->model = $this->modelClass ? new $this->modelClass : $this->modelObject;
    }

    public function run()
    {
        $file = UploadedFile::getInstanceByName($this->inputName);

        if (!$file) {
            return $this->response(
                ['error' => Yii::t('filemanager-yii2', 'An error occured, try again later…')]
            );
        }

        $rules = $this->model->fileRules($this->attribute, true);
        $type = $this->model->fileOption($this->attribute, 'type', 'image');

        $model = new DynamicModel(compact('file'));

        $maxFiles = ArrayHelper::getValue($rules, 'maxFiles');
        if ($maxFiles !== null && $maxFiles > 1) {
            $model->file = [$model->file];
        }

        $model->addRule('file', $type, $rules)->validate();
        if ($model->hasErrors()) {
            return $this->response(['error' => $model->getFirstError('file')]);
        }

        if (is_array($model->file)) {
            $model->file = $model->file[0];
        }

        return $this->save($model->file);
    }

    /**
     * Upload
     *
     * @param UploadedFile $file
     * @return string JSON
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function save($file)
    {
        $file = $this->model->createFile($this->attribute, $file->tempName, $file->name);
        if ($file) {
            $presetAfterUpload = $this->model->filePresetAfterUpload($this->attribute);
            if (count($presetAfterUpload)) {
                $this->applyPreset($presetAfterUpload, $file);
            }
            $template = $this->model->fileOption($this->attribute, 'template');
            if ($template) {
                return $this->response(
                    $this->controller->renderFile(Yii::getAlias($template), [
                        'file' => $file,
                        'model' => $this->model,
                        'attribute' => $this->attribute
                    ])
                );
            }
            return $this->response([
                $this->resultFieldId => $file->getPrimaryKey(),
                $this->resultFieldPath => $this->model->fileUrl($this->attribute, $file),
            ]);
        }
        return $this->response(['error' => Yii::t('filemanager-yii2', 'Error saving file')]); // @codeCoverageIgnore
    }

    /**
     * Apply preset for file
     *
     * @param array $presetAfterUpload
     * @param ActiveRecord $file The file model
     * @return void
     */
    private function applyPreset($presetAfterUpload, $file)
    {
        foreach ($presetAfterUpload as $preset) {
            $this->model->thumbUrl($this->attribute, $preset, $file);
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
