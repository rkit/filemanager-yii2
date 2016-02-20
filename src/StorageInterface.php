<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager;

/**
 * The interface for storages
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 * @since 2.0
 */
interface StorageInterface
{
    /**
     * Path to the file
     *
     * @return string
     */
    public function path();

    /**
     * Save the file to the storage
     * If the file is temporary, then in the temporary directory
     *
     * @return \rkit\filemanager\models\File|bool
     */
    public function save($path);

    /**
     * Save the temporary file to the storage
     *
     * @return bool
     */
    public function saveTemporaryFileToStorage();

    /**
     * Deletes the file from the storage
     */
    public function delete();
}
