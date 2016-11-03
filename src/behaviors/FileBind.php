<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager\behaviors;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\Query;

class FileBind
{
    public function bind($model, $attribute, $files)
    {
        $newFiles = $this->newFiles($model, $attribute, $files);
        if (!count($newFiles)) {
            return false;
        }

        $currentFiles = $this->files($model, $attribute);
        $currentRelations = $this->relations($model, $attribute);
        $insertData = $this->prepareInsertRelations($model, $attribute, $newFiles);
        $updateData = $this->prepareUpdateRelations($model, $attribute, $newFiles, $currentRelations);

        $resultFiles = $insertData['files'] + $updateData['files'];
        if (!count($resultFiles)) {
            return false;
        }

        $this->delete($model, $attribute, array_diff_key($currentFiles, $resultFiles));
        $this->save($model, $attribute, $insertData, $updateData, $resultFiles);

        return $resultFiles;
    }

    private function save($model, $attribute, $insertData, $updateData, $resultFiles)
    {
        if (count($insertData['rows'])) {
            $this->insertRelations($model, $attribute, $insertData['rows'], $insertData['columns']);
        }
        if (count($updateData['rows'])) {
            $this->updateRelations($model, $attribute, $updateData['rows']);
        }
        if (count($resultFiles)) {
            $this->updateFiles($model, $attribute, $resultFiles);
        }
    }

    private function newFiles($model, $attribute, $fileIds)
    {
        $relation = $model->fileRelation($attribute);
        $fileModel = $relation->modelClass;

        return $fileModel::find()
            ->where([key($relation->link) => $fileIds])
            ->indexBy(key($relation->link))
            ->all();
    }

    private function prepareInsertRelations($model, $attribute, $newFiles)
    {
        $ownerId = $model->getPrimaryKey();
        $relation = $model->fileRelation($attribute);
        $uploadedFiles = $model->fileState($attribute);
        $handlerExtraFields = $model->fileOption($attribute, 'extraFields');

        $files = [];
        $rows = [];
        $extraFields = [];
        foreach ($uploadedFiles as $fileId) {
            if (isset($newFiles[$fileId])) {
                $file = $newFiles[$fileId];
                $row = [$ownerId, $fileId];
                if ($handlerExtraFields) {
                    $fields = [
                        key($relation->via->link) => $ownerId,
                        current($relation->link) => $fileId,
                    ];
                    $extraFields = $handlerExtraFields($file, $fields);
                    $row = array_merge($row, array_values($extraFields));
                }
                $rows[] = $row;
                $files[$file->getPrimaryKey()] = $file;
            }
        }

        $columns = [key($relation->via->link), current($relation->link)];
        $columns = array_merge($columns, array_keys($extraFields));

        return ['rows' => $rows, 'files' => $files, 'columns' => $columns];
    }

    private function prepareUpdateRelations($model, $attribute, $newFiles, $currentRelations)
    {
        $relation = $model->fileRelation($attribute);
        $handlerExtraFields = $model->fileOption($attribute, 'extraFields');

        $files = [];
        $rows = [];
        foreach ($currentRelations as $fields) {
            if (isset($newFiles[$fields[current($relation->link)]])) {
                $file = $newFiles[$fields[current($relation->link)]];
                if ($handlerExtraFields) {
                    $extraFields = $handlerExtraFields($file, $fields);
                    $fieldChanged = (bool)count(array_diff_assoc($extraFields, $fields));
                    if ($fieldChanged) {
                        $rows[$file->getPrimaryKey()] = $extraFields;
                    }
                }
                $files[$file->getPrimaryKey()] = $file;
            }
        }
        return ['rows' => $rows, 'files' => $files];
    }

    private function insertRelations($model, $attribute, $rows, $columns)
    {
        $relation = $model->fileRelation($attribute);
        Yii::$app->getDb()->createCommand()
            ->batchInsert($relation->via->from[0], $columns, $rows)
            ->execute();
    }

    private function updateRelations($model, $attribute, $rows)
    {
        $relation = $model->fileRelation($attribute);
        $ownerId = $model->getPrimaryKey();
        $db = Yii::$app->getDb()->createCommand();

        foreach ($rows as $fileId => $row) {
            $db->update($relation->via->from[0], $row, [
                key($relation->via->link) => $ownerId,
                current($relation->link) => $fileId
            ])->execute();
        }
    }

    private function updateFiles($model, $attribute, $files)
    {
        $handlerUpdateFile = $model->fileOption($attribute, 'updateFile');
        if ($handlerUpdateFile) {
            foreach ($files as $file) {
                $fileUpd = $handlerUpdateFile($file);
                $dirtyAttributes = $fileUpd->getDirtyAttributes();
                if (count($dirtyAttributes)) {
                    $fileUpd->updateAttributes($dirtyAttributes);
                }
            }
        }
    }

    public function delete($model, $attribute, $files)
    {
        $relation = $model->fileRelation($attribute);
        $storage = $model->fileStorage($attribute);
        $presets = array_keys($model->fileOption($attribute, 'preset', []));
        $handlerTemplatePath = $model->fileOption($attribute, 'templatePath');

        $db = Yii::$app->getDb()->createCommand();
        foreach ($files as $file) {
            foreach ($presets as $preset) {
                $thumbPath = $model->thumbPath($attribute, $preset, $file);
                $filePath = str_replace($storage->path, '', $thumbPath);
                if ($storage->has($filePath)) {
                    $storage->delete($filePath);
                }
            }
            if ($file->delete()) {
                $db->delete($relation->via->from[0], [
                    current($relation->link) => $file->getPrimaryKey()
                ])->execute();
                $filePath = $handlerTemplatePath($file);
                if ($storage->has($filePath)) {
                    $storage->delete($filePath);
                }
            }
        }
    }

    public function relations($model, $attribute)
    {
        $relation = $model->fileRelation($attribute);
        $handlerRelationQuery = $model->fileOption($attribute, 'relationQuery');
        $query = null;

        if ($handlerRelationQuery) {
            $query = Query::create($handlerRelationQuery($model->find()));
        }

        $query = $query ?: new Query();
        return $query
            ->from($relation->via->from[0])
            ->andWhere([key($relation->via->link) => $model->getPrimaryKey()])
            ->indexBy(current($relation->link))
            ->all();
    }

    public function files($model, $attribute)
    {
        $relation = $model->fileRelation($attribute);
        $relationName = $model->fileOption($attribute, 'relation');
        $handlerRelationQuery = $model->fileOption($attribute, 'relationQuery');

        $query = call_user_func_array([$model, 'get' . $relationName], [$handlerRelationQuery]);
        return $query->indexBy(key($relation->link))->all();
    }

    public function file($model, $attribute)
    {
        $relation = $model->fileOption($attribute, 'relation');
        $handlerRelationQuery = $model->fileOption($attribute, 'relationQuery');

        $query = call_user_func_array([$model, 'get' . $relation], [$handlerRelationQuery]);
        return $query->one();
    }
}
