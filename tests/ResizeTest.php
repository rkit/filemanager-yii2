<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

class ResizeTest extends BaseTest
{
    private $modelClass = 'tests\data\models\News';

    public function setUp()
    {
        parent::setUp();
    }

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
        list($imgWidth, $imgHeight) = getimagesize($path);
        $this->assertTrue($imgWidth === $width);
        $this->assertTrue($imgHeight === $height);
    }

    public function testResize()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $thumb = $model->thumbPath('image', '200x200');
        $this->assertContains('200x200', $thumb);
        $this->assertFileExists($thumb);
        $this->checkImageSize($thumb, 200, 200);

        // for check cache thumb
        $model->thumbUrl('image', '200x200');
    }

    public function testResizeAndApplyPresetAfterUpload()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $path = $model->filePath('image');
        $fileName = pathinfo($path, PATHINFO_FILENAME);
        $thumb220 = str_replace($fileName, '220x220_' . $fileName, $path);

        $this->assertFileExists($thumb220);
        $this->checkImageSize($thumb220, 220, 220);
    }

    public function testResizeAndApplyAllPresetAfterUpload()
    {
        $model = $this->createObject($this->modelClass, [
            'applyPresetAfterUpload' => '*',
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $path = $model->filePath('image');
        $fileName = pathinfo($path, PATHINFO_FILENAME);

        $thumb200 = str_replace($fileName, '200x200_' . $fileName, $path);
        $thumb220 = str_replace($fileName, '220x220_' . $fileName, $path);

        $this->assertFileExists($thumb200);
        $this->checkImageSize($thumb200, 200, 200);

        $this->assertFileExists($thumb220);
        $this->checkImageSize($thumb220, 220, 220);
    }

    public function testResizeAndReplace()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-500'
        ]);

        $model->image = $response['id'];
        $model->save();

        $thumb = $model->thumbPath('image', '400x400');
        $this->assertContains('400x400', $thumb);
        $this->assertFileNotExists($thumb);
        $this->checkImageSize($model->filePath('image'), 400, 400);
    }
}
