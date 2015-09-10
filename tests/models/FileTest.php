<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
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

        $this->assertCount(2, $file->getStatuses());

        $file->status = File::STATUS_UNPROTECTED;
        $this->assertTrue($file->isUnprotected());
        $this->assertContains('Unprotected, access from the web', $file->getStatusName());

        $file->status = File::STATUS_PROTECTED;
        $this->assertTrue($file->isProtected());
        $this->assertContains('Protected', $file->getStatusName());
    }
}
