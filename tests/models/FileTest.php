<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use Yii;
use rkit\filemanager\models\File;

class FileTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Yii::$app->fileManager->registerTranslations();
    }

    public function testStatuses()
    {
        $file = new File();

        $file->protected = false;
        $this->assertTrue($file->isUnprotected());

        $file->protected = true;
        $this->assertTrue($file->isProtected());
    }
}
