<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use Yii;
use rkit\filemanager\storages\LocalStorage;

class LocalStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Exception
     * @expectedExceptionMessage The file is not initialized
     */
    public function testFailGetFile()
    {
        $storage = new LocalStorage();
        $storage->getFile();
    }
}
