<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use Yii;
use tests\data\News;
use rkit\filemanager\models\File;

class MultipleUploadTest extends BaseTest
{
    public function testClearGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]);

        $this->assertCount(1, $model->getFiles('image_gallery'));

        $model->image_gallery = [];
        $model->save();

        $this->assertCount(0, $model->getFiles('image_gallery'));
    }

    public function testWrongGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]);

        $model->image_gallery = ['1000' => 'test'];
        $model->save();

        $this->assertCount(0, $model->getFiles('image_gallery'));
    }

    public function testFailSaveGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]);

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-500'
        ]);

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        unlink($file->getStorage()->path(true));

        $model->image_gallery = [$response['id'] => 'test'];
        $model->save();

        $files = $model->getFiles('image_gallery');

        $this->assertCount(0, $files);
    }
}
