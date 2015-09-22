<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace tests;

use Yii;
use tests\data\News;
use rkit\filemanager\models\File;

class GalleryUploadTest extends BaseTest
{
    public function testUploadUnprotectedGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]));

        foreach ($files as $file) {
            $this->assertTrue($file->isUnprotected());
            $this->assertContains(Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected), $file->path(true));
        }
    }

    public function testUploadProtectedGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery_protected',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php'),
        ]));

        foreach ($files as $file) {
            $this->assertTrue($file->isProtected());
            $this->assertContains(Yii::getAlias(Yii::$app->fileManager->uploadDirProtected), $file->path(true));
        }
    }

    public function testSetEmptyGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]));

        $model->image_gallery = [];
        $model->save();

        $files = $model->getFiles('image_gallery');
        $this->assertCount(0, $files);
    }

    public function testAnotherOwnerGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]));

        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-500',
            'ownerId'   => 100,
            'saveAfterUpload' => true
        ]);

        $model->image_gallery = [$response['id'] => 'test'];
        $model->save();

        $file = File::findOne($response['id']);

        $this->assertTrue(is_object($file));
        $this->assertCount(0, $model->getFiles('image_gallery'));
    }

    public function testEmptyGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]));

        $this->assertCount(1, $model->getFiles('image_gallery'));

        $model->image_gallery = [];
        $model->save();

        $this->assertCount(0, $model->getFiles('image_gallery'));
    }

    public function testWrongGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]));

        $model->image_gallery = ['1000' => 'test'];
        $model->save();

        $this->assertCount(0, $model->getFiles('image_gallery'));
    }

    public function testSaveNotTmpGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]));

        $this->assertCount(1, $model->getFiles('image_gallery'));

        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-500',
            'ownerId' => $model->id,
            'saveAfterUpload' => true
        ]);

        $model->image_gallery = [$response['id'] => 'test'];
        $model->save();

        $this->assertCount(2, $model->getFiles('image_gallery'));
    }

    public function testFailSaveGallery()
    {
        extract($this->uploadGallery([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]));

        $oldFiles = $files;

        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-500'
        ]);

        $file = File::findOne($response['id']);
        unlink($file->path(true));

        $model->image_gallery = [$response['id'] => 'test'];
        $model->save();

        $files = $model->getFiles('image_gallery');

        $this->assertCount(1, $files);
        $this->assertTrue($files[0]->id === $oldFiles[0]->id);
    }
}
