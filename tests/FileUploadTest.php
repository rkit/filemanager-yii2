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
    public function testNotTemporary()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'temporary' => false,
        ]);

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkNotTmpFile($file, 0, $ownerType);
    }

    public function testTemporary()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'temporary' => true,
        ]);

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkTmpFile($file, 0, $ownerType);
    }

    public function testResultFieldId()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'resultFieldId' => 'customFieldId',
        ]);

        $this->assertArrayHasKey('customFieldId', $response);
    }

    public function testResultFieldPath()
    {
        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'resultFieldPath' => 'customFieldPath',
        ]);

        $this->assertArrayHasKey('customFieldPath', $response);
    }

    public function testSaveFileId()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300',
            'temporary' => true
        ]);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_id');
        $this->checkNotTmpFile($file, $model->id, $ownerType);
        $this->assertTrue($model->image_id === $file->id);
    }

    public function testFailSaveFileId()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300',
            'temporary' => false
        ]);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_id');
        $this->checkNotTmpFile($file, 0, $ownerType);
        $this->assertTrue($model->image_id === 0);
    }

    public function testSaveFilePath()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'temporary' => true
        ]);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkNotTmpFile($file, $model->id, $ownerType);
        $this->assertTrue($model->image_path === $file->getStorage()->path());
    }

    public function testFailSaveFilePath()
    {
        list($file, $model) = $this->uploadFileAndBindToModel([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'temporary' => false
        ]);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkNotTmpFile($file, 0, $ownerType);
        $this->assertEmpty($model->image_path);
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

        $file = File::findOne($file->id);
        $this->assertNull($file);
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

    public function testEmptyFilePath()
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

    public function testEmptyFileId()
    {
        $model = new News();

        $response = $this->runUploadAction([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-500'
        ]);

        $model->image_id = 0;
        $model->save();

        $this->assertTrue($model->image_id === 0);
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
        ]);

        $model->image_id = 100;
        $model->save();

        $this->assertTrue($model->image_id === $file->id);
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

    public function testFailSaveWithUnlinkFile()
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

    public function testManualBindFile()
    {
        $model = new News();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $model->createFile('image_path', $file, null, true);

        $model->image_path = $file->id;
        $model->save();

        $this->assertTrue($model->getFile('image_path')->id === $file->id);
    }

    public function testWrongManualBindFile()
    {
        $model = new News();
        $model->image_path = 100;
        $model->save();

        $this->assertEmpty($model->image_path);
    }

    public function testManualCreateFileWithDefaultTitle()
    {
        $model = new News();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $model->createFile('image_id', $file, null, true);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_id');
        $this->checkTmpFile($file, 0, $ownerType);
        $this->assertTrue($file->isProtected());
        $this->assertTrue($file->title === 'test_create_from_path_100x100');
    }

    public function testManualCreateFileWithCustomTitle()
    {
        $model = new News();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $model->createFile('image_id', $file, 'title 1', true);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_id');
        $this->checkTmpFile($file, 0, $ownerType);
        $this->assertTrue($file->isProtected());
        $this->assertTrue($file->title === 'title 1');
    }

    public function testManualCreateNotTmpUnprotectedFile()
    {
        $model = new News();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $model->createFile('image_path', $file, null, false);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkNotTmpFile($file, 0, $ownerType);
        $this->assertTrue($file->isUnProtected());
    }

    public function testManualCreateNotTmpProtectedFile()
    {
        $model = new News();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $model->createFile('image_id', $file, null, false);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_id');
        $this->checkNotTmpFile($file, 0, $ownerType);
        $this->assertTrue($file->isProtected());
    }

    public function testManualCreateTmpUnprotectedFile()
    {
        $model = new News();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $model->createFile('image_path', $file, null, true);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_path');
        $this->checkTmpFile($file, 0, $ownerType);
        $this->assertTrue($file->isUnprotected());
    }

    public function testManualCreateTmpProtectedFile()
    {
        $model = new News();

        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = $model->createFile('image_id', $file, null, true);

        $ownerType = Yii::$app->fileManager->getOwnerType('news.image_id');
        $this->checkTmpFile($file, 0, $ownerType);
        $this->assertTrue($file->isProtected());
    }

    public function testManualCreateFileWithoutExtension()
    {
        $file = $this->prepareFile('file-300-without-extension', 'test_without_extension');

        $model = new News();
        $file = $model->createFile('image_id', $file, 'title 1', true);
        $this->assertTrue($file->extension === 'png');
    }

    public function testManualCreateFileWitExtensionInTitle()
    {
        $file = $this->prepareFile('file-300-without-extension', 'test_with_extension_in_title');

        $model = new News();
        $file = $model->createFile('image_id', $file, 'title.jpg', true);
        $this->assertTrue($file->extension === 'png');
    }

    public function testFailManualCreateFile()
    {
        $model = new News();
        $file = $model->createFile('image_id', '/test/fail', 'title 1', true);
        $this->assertFalse($file);
    }

    public function testFailSaveToStorage()
    {
        $file = new File();
        $file->setStorage($this->storage);
        $file = $file->getStorage()->save('/test/fail.jpg');

        $this->assertFalse($file);
    }
}
