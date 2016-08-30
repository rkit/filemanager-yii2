<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager\behaviors;

use yii\helpers\ArrayHelper;
use rkit\filemanager\models\File;

/**
 * The bind class for FileBehavior
 */
class FileBind
{
    /**
     * Bind the file to the with owner
     * @internal
     *
     * @param Storage $storage The storage for the file
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     * @param int $fileId The id of the file
     * @return rkit\filemanager\models\File|bool
     */
    public function bindSingle($storage, $ownerId, $ownerType, $fileId)
    {
        $file = File::findOne($fileId);
        if ($file && $file->isOwner($ownerId, $ownerType)) {
            $file->setStorage($storage);
            if ($file->tmp) {
                $file = $this->saveTmpDirToStorage($file, $ownerId);
                if ($file) {
                    $this->deleteCurrentFiles($storage, $ownerId, $ownerType, [$file->id => $file]);
                    $file->updateAttributes($file->getDirtyAttributes());
                    $file->setStorage($storage);
                }
            }
            return $file;
        }

        return false;
    }

    /**
     * Bind files to the with owner
     *
     * @param Storage $storage The storage for the files
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     * @param array $files Array of ids
     * @return rkit\filemanager\models\File[]|bool
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function bindMultiple($storage, $ownerId, $ownerType, $files)
    {
        $newFiles = ArrayHelper::index(File::findAll(array_keys($files)), 'id');

        if (count($newFiles)) {
            foreach ($newFiles as $fileId => $file) {
                if ($file->isOwner($ownerId, $ownerType)) {
                    $file->setStorage($storage);
                    if ($file->tmp) {
                        $file = $this->saveTmpDirToStorage($file, $ownerId);
                    }
                    if ($file) {
                        $file->position = @array_search($file->id, array_keys($files)) + 1;
                        $file->title = ArrayHelper::getValue($files, $file->id, $file->title);
                        $file->updateAttributes($file->getDirtyAttributes());
                        continue;
                    }
                }
                unset($newFiles[$fileId]);
                continue;
            }
            $this->deleteCurrentFiles($storage, $ownerId, $ownerType, $newFiles);
        } else {
            $this->deleteCurrentFiles($storage, $ownerId, $ownerType);
        }

        return count($newFiles) ? $newFiles : false;
    }

    /**
     * Save the temporary file to the storage
     *
     * @param File $file
     * @param int $ownerId The id of the owner
     * @return rkit\filemanager\models\File|bool
     */
    private function saveTmpDirToStorage(File $file, $ownerId)
    {
        $file->owner_id = $ownerId;
        $file->tmp = false;
        if ($file->getStorage()->saveTemporaryFileToStorage()) {
            return $file;
        }

        return false;
    }

    /**
     * Delete current files
     *
     * @param Storage $storage
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     * @param rkit\filemanager\models\File[] $exceptFiles
     * @return void
     */
    private function deleteCurrentFiles($storage, $ownerId, $ownerType, $exceptFiles = [])
    {
        $currentFiles = File::findAllByOwner($ownerId, $ownerType);
        foreach ($currentFiles as $currFile) {
            $isExceptFiles = count($exceptFiles) && array_key_exists($currFile->id, $exceptFiles);
            if (!$isExceptFiles) {
                $currFile->setStorage($storage);
                $currFile->delete();
            }
        }
    }
}
