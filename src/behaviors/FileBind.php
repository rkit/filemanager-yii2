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
 * This is the bind class for FileBehavior
 */
class FileBind
{
    /**
     * Bind files with owner
     *
     * @param Storage $storage
     * @param int $ownerId
     * @param int $ownerType
     * @param int|array $fileId
     * @return File|File[]|bool
     */
    public function setBind($storage, $ownerId, $ownerType, $fileId)
    {
        if ($fileId === [] || $fileId === '') {
            (new File())->deleteByOwner($storage, $ownerId, $ownerType);
            return true;
        }

        if (is_array($fileId)) {
            return $this->bindMultiple($storage, $ownerId, $ownerType, $fileId);
        } else {
            return $this->bindSingle($storage, $ownerId, $ownerType, $fileId);
        }
    }

    /**
     * Bind the file to the with owner
     *
     * @param Storage $storage
     * @param int $ownerId
     * @param int $ownerType
     * @param int $fileId
     * @return rkit\filemanager\models\File|bool
     */
    private function bindSingle($storage, $ownerId, $ownerType, $fileId)
    {
        $file = $fileId ? File::findOne($fileId) : false;

        if ($file && $file->isOwner($ownerId, $ownerType)) {
            $file->setStorage($storage);
            if ($file->tmp) {
                $file = $this->saveTmpDirToStorage($file, $ownerId);
                if ($file) {
                    $this->deleteCurrentFiles($storage, $ownerId, $ownerType, $file);
                    $file->updateAttributes($file->getDirtyAttributes());
                }
            }
            return $file;
        }

        return false;
    }

    /**
     * Bind files to the with owner
     *
     * @param Storage $storage
     * @param int $ownerId
     * @param int $ownerType
     * @param array $files
     * @return rkit\filemanager\models\File[]|bool
     */
    private function bindMultiple($storage, $ownerId, $ownerType, $files)
    {
        $files = ArrayHelper::getValue($files, 'files', []);
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
            $this->deleteCurrentFiles($storage, $ownerId, $ownerType, null, $newFiles);
        } else {
            $this->deleteCurrentFiles($storage, $ownerId, $ownerType);
        }

        return count($newFiles) ? $newFiles : false;
    }

    /**
     * Save temporary directory to the storage
     *
     * @param File $file
     * @param int $ownerId
     * @return rkit\filemanager\models\File|bool
     */
    private function saveTmpDirToStorage(File $file, $ownerId)
    {
        $file->owner_id = $ownerId;
        $file->tmp = false;
        if ($file->getStorage()->saveTmpDirToStorage()) {
            return $file;
        }

        return false;
    }

    /**
     * Delete current files
     *
     * @param Storage $storage
     * @param int $ownerId
     * @param int $ownerType
     * @param rkit\filemanager\models\File $exceptFile
     * @param rkit\filemanager\models\File[] $exceptFiles
     * @return void
     */
    private function deleteCurrentFiles($storage, $ownerId, $ownerType, $exceptFile = null, $exceptFiles = [])
    {
        $currentFiles = File::findAllByOwner($ownerId, $ownerType);
        foreach ($currentFiles as $currFile) {
            $isExceptFile = $exceptFile !== null && $currFile->id === $exceptFile->id;
            $isExceptFiles = count($exceptFiles) && array_key_exists($currFile->id, $exceptFiles);
            if (!$isExceptFile && !$isExceptFiles) {
                $currFile->setStorage($storage);
                $currFile->delete();
            }
        }
    }
}
