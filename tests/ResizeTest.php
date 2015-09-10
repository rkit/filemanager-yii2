<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace tests;

use Yii;
use tests\data\News;

class ResizeTest extends BaseTest
{
    /**
     * Check image size
     *
     * @param string $path
     * @param int $width
     * @param int $height
     * @return void
     */
    protected function checkImageSize($path, $width, $height)
    {
        list($imgWidth, $imgHeight, $type, $attr) = getimagesize($path);
        $this->assertTrue($imgWidth === $width);
        $this->assertTrue($imgHeight === $height);
    }

    public function testResize()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $thumb = $model->thumb('image_path', '200x200', null, true);
        $this->assertContains('200x200', $thumb);
        $this->assertFileExists($thumb);
        $this->checkImageSize($thumb, 200, 200);

        // for check cache thumb
        $model->thumb('image_path', '200x200');
    }

    public function testResizeAndReplace()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500'
        ]));

        $thumb = $model->thumb('image_path', '400x400', null, true);
        $this->assertContains('400x400', $thumb);
        $this->assertFileNotExists($thumb);
        $this->checkImageSize($file->path(true), 400, 400);
    }
}
