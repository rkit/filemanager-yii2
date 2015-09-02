<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace app\filemanager;

use yii\base\Component;
use yii\base\InvalidParamException;

/**
 * File Manager.
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 * @since 1.0
 */
class FileManager extends Component
{
    /**
     * @var string Path to upload.
     */
    public $uploadDir = 'uploads';
    /**
     * @var array Type of owner in format: title:string => type:int
     */
    public $ownerTypes = [];

    public function getOwnerType($ownerType)
    {
        if (!isset($this->ownerTypes[$ownerType])) {
            throw new InvalidParamException('This type `' . $ownerType . '` is not found');
        }

        return $this->ownerTypes[$ownerType];
    }
}
