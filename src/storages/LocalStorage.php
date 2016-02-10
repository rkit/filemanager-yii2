<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager\storages;

use Yii;
use yii\helpers\FileHelper;
use rkit\filemanager\Storage;

/**
 * This is the local storage for files
 */
class LocalStorage extends Storage
{
    /**
     * Upload directory
     *
     * @return string
     */
    private function uploadDir()
    {
        if ($this->getFile()->isProtected()) {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirProtected);
        } else {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected);
        }
    }

    /**
     * Path to the temporary directory of the file
     *
     * @param bool $realPath
     * @return string
     */
    private function dirTmp($realPath = false)
    {
        $file = $this->getFile();

        $path  = $realPath ? $this->uploadDir() : '';
        $path .= '/' . Yii::$app->fileManager->publicPath . '/tmp';
        $path .= '/' . $file->getDateOfFile();
        $path .= '/' . $file->owner_type . '/' . $file->id;

        return $path;
    }

    /**
     * Path to the directory of the file
     *
     * @param bool $realPath
     * @return string
     */
    private function dir($realPath = false)
    {
        $file = $this->getFile();

        if ($file->tmp) {
            return $this->dirTmp($realPath);
        } else {
            $path  = $realPath ? $this->uploadDir() : '';
            $path .= '/' . Yii::$app->fileManager->publicPath;
            $path .= '/' . $file->getDateOfFile();
            $path .= '/' . $file->owner_type . '/' . $file->owner_id . '/' . $file->id;

            return $path;
        }
    }

    /**
     * Path to the temporary file
     *
     * @param bool $realPath
     * @return string
     */
    private function pathTmp($realPath = false)
    {
        return $this->dirTmp($realPath) . '/'. $this->getFile()->name;
    }

    /**
     * Path to the file
     *
     * @param bool $realPath
     * @return string
     */
    public function path($realPath = false)
    {
        return $this->dir($realPath) . '/'. $this->getFile()->name;
    }

    /**
     * Save the file to the storage or temporary directory
     *
     * @param string $path
     * @param bool $isUploadedFile File has been uploaded or manually created
     * @return \rkit\filemanager\models\File|bool
     */
    public function save($path, $isUploadedFile = true)
    {
        $file = $this->getFile();
        if (file_exists($path)) {
            if (FileHelper::createDirectory($this->dir(true))) {
                $isConsole = Yii::$app instanceof \yii\console\Application;
                if (!$isUploadedFile || $isConsole) {
                    $saved = rename($path, $this->path(true));
                } else {
                    $saved = move_uploaded_file($path, $this->path(true)); // @codeCoverageIgnore
                }

                if ($saved) {
                    return $file;
                }
            } // @codeCoverageIgnore
        } // @codeCoverageIgnore

        return false;
    }

    /**
     * Save temporary directory to the storage
     *
     * @return bool
     */
    public function saveTmpDirToStorage()
    {
        if (file_exists($this->pathTmp(true))) {
            FileHelper::copyDirectory($this->dirTmp(true), $this->dir(true));
            FileHelper::removeDirectory($this->dirTmp(true));
            return true;
        } // @codeCoverageIgnore

        return false;
    }

    public function delete()
    {
        FileHelper::removeDirectory($this->dir(true));
    }
}
