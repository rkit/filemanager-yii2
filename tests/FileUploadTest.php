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

class FileUploadTest extends BaseTest
{
    public function testUploadUnprotectedFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $this->assertTrue(is_object($model->getFile('image_path')));
        $this->assertTrue($file->isUnprotected());
        $this->assertContains(
            Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected),
            $file->getStorage()->path(true)
        );
    }

    public function testUploadProtectedFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false);

        $this->assertTrue($file->isProtected());
        $this->assertContains(
            Yii::getAlias(Yii::$app->fileManager->uploadDirProtected),
            $file->getStorage()->path(true)
        );
    }

    public function testNotTemporary()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'temporary' => false,
            'ownerId' => 0
        ]);

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkNotTmpFile($file, 0, $ownerType);
    }

    public function testNotTemporaryWithOwnerId()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'temporary' => false,
            'ownerId' => 100
        ]);

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkNotTmpFile($file, 100, $ownerType);
    }

    public function testResultFieldId()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'resultFieldId' => 'customName',
        ]);

        $this->assertArrayHasKey('customName', $response);
    }

    public function testRsultFieldPath()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'resultFieldPath' => 'customName',
        ]);

        $this->assertArrayHasKey('customName', $response);
    }

    public function testSaveFileId()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false);

        $this->assertTrue(is_numeric($model->image_id));
    }

    public function testDeleteFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $file->delete();
        $this->assertFileNotExists($file->getStorage()->path(true));
    }

    public function testDeleteModel()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $model->delete();
        $this->assertFileNotExists($file->getStorage()->path(true));

        $file = File::findOne($file->id);
        $this->assertNull($file);
    }

    public function testSetEmptyFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $model->image_path = '';
        $model->save();

        $file = File::findOne($file->id);
        $this->assertNull($file);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage The "modelName" attribute must be set.
     */
    public function testEmptyModelName()
    {
        $this->runUploadAction([
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);
    }

    public function testWrongInputName()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'fail'
        ]);

        $this->assertCount(1, $response);
        $this->assertTrue(isset($response['error']));
    }

    public function testWrongImageSize()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-100'
        ]);

        $this->assertCount(1, $response);
        $this->assertTrue(isset($response['error']));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This type `test` is not found
     */
    public function testWrongOwnerType()
    {
        Yii::$app->fileManager->getOwnerType('test');
    }

    public function testWrongFilePath()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $model->image_path = 'test';
        $model->save();

        $this->assertTrue($model->image_path === $file->getStorage()->path());
    }

    public function testWrongFileId()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false);

        $model->image_id = 100;
        $model->save();

        $this->assertTrue($model->image_id === $file->id);

        $model = new News();
        $model->image_id = 100;
        $model->save();

        $this->assertTrue($model->image_id === 0);
    }

    public function testDeleteUnnecessaryFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500'
        ]);

        $model->image_path = $response['id'];
        $model->save();

        $this->assertNull(File::findOne($file->id));
    }

    public function testAnotherOwnerFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500',
            'ownerId'   => 100,
            'temporary' => true
        ]);

        $model->image_path = $response['id'];
        $model->save();

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $this->assertFalse($file->getStorage()->path() === $model->image_path);
    }

    public function testSaveNotTmpFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500',
            'ownerId' => $model->id,
            'temporary' => false
        ]);

        $model->image_path = $response['id'];
        $model->save();

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $this->assertTrue($file->getStorage()->path() === $model->image_path);
    }

    public function testFailSaveFile()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500'
        ]);

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        unlink($file->getStorage()->path(true));

        $model->image_path = $response['id'];
        $model->save();

        $this->assertFalse($file->getStorage()->path() === $model->image_path);
    }

    public function testNotTmpUnprotectedCreateFromPath()
    {
        $decoder = Yii::$app->fileManager->getDecoder();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $decoder->createFromPath($this->storage, $file, 100, 200, false, false);

        $this->assertTrue(is_object($file));
        $this->assertFalse($file->isTmp());
        $this->assertTrue($file->isUnprotected());
        $this->assertFileExists($file->getStorage()->path(true));
    }

    public function testNotTmpProtectedCreateFromPath()
    {
        $decoder = Yii::$app->fileManager->getDecoder();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $decoder->createFromPath($this->storage, $file, 100, 200, false, true);

        $this->assertTrue(is_object($file));
        $this->assertFalse($file->isTmp());
        $this->assertTrue($file->isProtected());
        $this->assertFileExists($file->getStorage()->path(true));
    }

    public function testTmpUnprotectedCreateFromPath()
    {
        $decoder = Yii::$app->fileManager->getDecoder();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $decoder->createFromPath($this->storage, $file, 100, 200, false, false);

        $this->assertTrue(is_object($file));
        $this->assertFalse($file->isTmp());
        $this->assertTrue($file->isUnprotected());
        $this->assertFileExists($file->getStorage()->path(true));
    }

    public function testTmpProtectedCreateFromPath()
    {
        $decoder = Yii::$app->fileManager->getDecoder();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $decoder->createFromPath($this->storage, $file, 100, 200, false, true);

        $this->assertTrue(is_object($file));
        $this->assertFalse($file->isTmp());
        $this->assertTrue($file->isProtected());
        $this->assertFileExists($file->getStorage()->path(true));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unable to create from `/test/fail.jpg`
     */
    public function testFailCreateFromPath()
    {
        $decoder = Yii::$app->fileManager->getDecoder();
        $file = $decoder->createFromPath($this->storage, '/test/fail.jpg', 100, 200, true, false);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unable to create from `/test/fail.jpg`
     */
    public function testFailCreateFromUploader()
    {
        $decoder = Yii::$app->fileManager->getDecoder();
        $file = $decoder->createFromUploader($this->storage, '/test/fail.jpg', 100, 200, true, false);
    }

    public function testFailSaveToStorage()
    {
        $file = new File();
        $file->setStorage($this->storage);
        $file = $file->getStorage()->save('/test/fail.jpg');

        $this->assertFalse($file);
    }
}
