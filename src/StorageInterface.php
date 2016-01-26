<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager;

/**
 * The interface for storages
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
     * Save the file to the storage or temporary directory
     *
     * @return \rkit\filemanager\models\File|bool
     */
    public function save($path);

    /**
     * Save temporary directory to the storage
     *
     * @return bool
     */
    public function saveTmpDirToStorage();

    /**
     * Deletes the file from the storage
     */
    public function delete();
}
