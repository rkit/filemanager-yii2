<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
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
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $thumb = $model->thumb('image_path', '200x200', null, true);
        $this->assertContains('200x200', $thumb);
        $this->assertFileExists($thumb);
        $this->checkImageSize($thumb, 200, 200);

        // for check cache thumb
        $model->thumb('image_path', '200x200', null, true);
    }

    public function testResizeProtected()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false);

        $path = $file->getStorage()->path();
        $thumb = $model->thumb('image_id', '200x200', $path, true);
        $this->assertTrue($model->image_id === $file->id);
        $this->assertContains('200x200', $thumb);
        $this->assertFileExists($thumb);
        $this->checkImageSize($thumb, 200, 200);

        // for check cache thumb
        $model->thumb('image_id', '200x200', $path, true);
    }

    public function testResizeAndApplyPresetAfterUpload()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $path = $file->getStorage()->path(true);
        $thumb220 = $model->generateThumbName($path, '220x220');
        $this->assertFileExists($thumb220);
        $this->checkImageSize($thumb220, 220, 220);

        $thumb200 = $model->generateThumbName($path, '200x200');
        $this->assertFileNotExists($thumb200);
    }

    public function testResizeProtectedAndApplyPresetAfterUpload()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false);

        $path = $file->getStorage()->path(true);
        $thumb220 = $model->generateThumbName($path, '220x220');
        $this->assertFileExists($thumb220);
        $this->checkImageSize($thumb220, 220, 220);

        $thumb200 = $model->generateThumbName($path, '200x200');
        $this->assertFileExists($thumb200);
        $this->checkImageSize($thumb200, 200, 200);
    }

    public function testResizeAndReplace()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500'
        ]);

        $thumb = $model->thumb('image_path', '400x400', null, true);
        $this->assertContains('400x400', $thumb);
        $this->assertFileNotExists($thumb);
        $this->checkImageSize($file->getStorage()->path(true), 400, 400);
    }
}
