<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager;

use yii\base\InvalidParamException;
use rkit\filemanager\StorageInterface;

/**
 * This is the base storage
 */
abstract class Storage implements StorageInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * Set a file
     *
     * @param File $file
     * @return string
     */
    public function setFile(\rkit\filemanager\models\File $file)
    {
        $this->file = $file;
    }

    /**
     * Get a file
     *
     * @return File
     * @throws InvalidParamException
     */
    public function getFile()
    {
        if ($this->file === null) {
            throw new InvalidParamException('The file is not initialized');
        }

        return $this->file;
    }
}
