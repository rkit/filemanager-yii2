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
    private $file;

    public function setFile(\rkit\filemanager\models\File $file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        if ($this->file === null) {
            throw new InvalidParamException('The file is not initialized');
        }

        return $this->file;
    }
}
