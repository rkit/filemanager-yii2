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
    public function testUploadUnprotectedGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]);

        foreach ($files as $file) {
            $this->assertTrue($file->isUnprotected());
            $this->assertContains(
                Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected),
                $file->getStorage()->path(true)
            );
        }
    }

    public function testUploadProtectedGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery_protected',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php'),
        ]);

        foreach ($files as $file) {
            $this->assertTrue($file->isProtected());
            $this->assertContains(
                Yii::getAlias(Yii::$app->fileManager->uploadDirProtected),
                $file->getStorage()->path(true)
            );
        }
    }

    public function testSetEmptyGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]);

        $model->image_gallery = [];
        $model->save();

        $files = $model->getFiles('image_gallery');
        $this->assertCount(0, $files);
    }

    public function testAnotherOwnerGallery()
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
            'inputName' => 'file-500',
            'ownerId'   => 100,
            'temporary' => false
        ]);

        $model->image_gallery = [$response['id'] => 'test'];
        $model->save();

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $this->assertTrue(is_object($file));
        $this->assertCount(0, $model->getFiles('image_gallery'));
    }

    public function testEmptyGallery()
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

    public function testRemoveFileFromGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]);

        $this->assertCount(1, $model->getFiles('image_gallery'));

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-500',
            'temporary' => true,
            'ownerId' => $model->id
        ]);

        $this->assertCount(2, $model->getFiles('image_gallery'));

        $model->image_gallery = [$response['id'] => 'test'];
        $model->save();

        $this->assertCount(1, $model->getFiles('image_gallery'));
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

    public function testSaveNotTmpGallery()
    {
        list($files, $model) = $this->uploadMultipleAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-300',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/views/gallery-item.php')
        ]);

        $this->assertCount(1, $model->getFiles('image_gallery'));

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_gallery',
            'inputName' => 'file-500',
            'ownerId' => $model->id,
            'temporary' => true
        ]);

        $model->image_gallery = [
            $files[0]->id => 'test2',
            $response['id'] => 'test'
        ];

        $model->save();

        $this->assertCount(2, $model->getFiles('image_gallery'));
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
