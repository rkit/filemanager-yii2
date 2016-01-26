<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager;

use yii\base\InvalidValueException;
use rkit\filemanager\models\File;

/**
 * File Manager
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 * @since 1.0
 */
class Decoder
{
    /**
     * Create file from uploader (UploadedFile)
     *
     * @param Storage $storage;
     * @param string $path Path to the file
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $temporary The file is temporary
     * @param bool $protected The file is protected, not available from the web
     * @return \rkit\filemanager\models\File|bool
     */
    public function createFromUploader(
        $storage,
        $path,
        $ownerId = -1,
        $ownerType = -1,
        $temporary = false,
        $protected = false
    ) {
        $file = File::create($path, $ownerId, $ownerType, $temporary, $protected);
        if ($file) {
            $file->setStorage($storage);
            return $file->getStorage()->save($path);
        }

        throw new InvalidValueException('Unable to create from `' . $path . '`');
    }

    /**
     * Create file from path
     *
     * @param Storage $storage;
     * @param string $path Path to the file or URL
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $temporary The file is temporary
     * @param bool $protected The file is protected, not available from the web
     * @return \rkit\filemanager\models\File|bool
     */
    public function createFromPath(
        $storage,
        $path,
        $ownerId = -1,
        $ownerType = -1,
        $temporary = false,
        $protected = false
    ) {
        $filePath = tempnam(sys_get_temp_dir(), 'FMR');
        if ($fileContent = @file_get_contents($path)) {
            file_put_contents($filePath, $fileContent);
            $file = File::create($filePath, $ownerId, $ownerType, $temporary, $protected);
            if ($file) {
                $file->setStorage($storage);
                return $file->getStorage()->save($filePath, false);
            }
        } // @codeCoverageIgnore

        throw new InvalidValueException('Unable to create from `' . $path . '`');
    }
}
