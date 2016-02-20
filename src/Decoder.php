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
 * The Decoder for creating files
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 * @since 2.0
 */
class Decoder
{
    /**
     * Create a file from the path
     *
     * @param Storage $storage;
     * @param string $path Path to the file
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     * @param bool $temporary The file is temporary
     * @param bool $protected The file is protected, not available from the web
     * @return \rkit\filemanager\models\File|bool
     * @throws InvalidValueException
     */
    public function createFromPath(
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
     * Create a file from the remote path
     *
     * @param Storage $storage;
     * @param string $path Path to the file or URL
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     * @param bool $temporary The file is temporary
     * @param bool $protected The file is protected, not available from the web
     * @return \rkit\filemanager\models\File|bool
     * @throws InvalidValueException
     */
    public function createFromRemotePath(
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
