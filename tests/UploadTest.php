<?php

namespace tests;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use tests\data\Controller;
use tests\data\News;
use rkit\filemanager\actions\UploadAction;
use rkit\filemanager\models\File;

class UploadTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $origFile = Yii::getAlias('@tests/data/files/300x300.png');
        $copyFile = Yii::getAlias('@tests/data/files/300x300_copy.png');
        copy($origFile, $copyFile);

        $_FILES = [
            'file' => [
                'name' => '300x300.png',
                'type' => 'image/png',
                'size' => 1299,
                'tmp_name' => $copyFile,
                'error' => 0
            ]
        ];
    }

    protected function tearDown()
    {
        File::deleteAll();
        News::deleteAll();

        FileHelper::removeDirectory(Yii::$app->fileManager->uploadDirProtected);
        FileHelper::removeDirectory(Yii::$app->fileManager->uploadDirUnprotected);

        @unlink(Yii::getAlias('@tests/data/files/300x300_copy.png'));
        unset($_FILES);
    }

    /**
     * Runs the action.
     *
     * @param array $config
     * @return mixed The result of the action.
     */
    protected function runAction($config)
    {
        $action = new UploadAction('upload', new Controller('test', Yii::$app), $config);
        return $action->run();
    }

    protected function checkUnprotectedTmpFile($file, $ownerId, $ownerType)
    {
        $this->assertTrue(is_object($file));
        $this->assertFileExists($file->path(true));
        $this->assertTrue($file->isUnprotected());
        $this->assertTrue((bool)$file->tmp);
        $this->assertTrue($file->owner_id === $ownerId);
        $this->assertTrue($file->owner_type === $ownerType);
    }

    protected function checkUnprotectedNotTmpFile($file, $ownerId, $ownerType)
    {
        $this->assertTrue(is_object($file));
        $this->assertFileExists($file->path(true));
        $this->assertTrue($file->isUnprotected());
        $this->assertTrue(!(bool)$file->tmp);
        $this->assertTrue($file->owner_id === $ownerId);
        $this->assertTrue($file->owner_type === $ownerType);
    }

    protected function baseUpload()
    {
        $response = $this->runAction($config = [
            'modelName' => News::className(),
            'attribute' => 'preview',
            'inputName' => 'file',
            'type' => 'image'
        ]);

        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('path', $response);

        $file = File::find($response['id'])->one();
        $this->checkUnprotectedTmpFile($file, -1, Yii::$app->fileManager->getOwnerType('news.preview'));

        $model = new News([
            'title' => 'test',
            'preview' => $file->id
        ]);

        $this->assertTrue($model->save());

        $file = File::find($file->id)->one();
        $this->checkUnprotectedNotTmpFile($file, $model->id, Yii::$app->fileManager->getOwnerType('news.preview'));
        $this->assertContains($model->preview, $file->path());

        return ['file' => $file, 'model' => $model];
    }

    protected function baseGalleryUpload()
    {
        $response = $this->runAction($config = [
            'modelName' => News::className(),
            'attribute' => 'gallery',
            'inputName' => 'file',
            'type' => 'image',
            'multiple' => true,
            'template' => Yii::getAlias('@tests/data/templates/gallery-item.php')
        ]);

        preg_match('/News\[gallery\]\[id(.*?)\]/', $response, $matches);

        $this->assertTrue(is_string($response));
        $this->assertTrue(isset($matches[1]));
        $this->assertTrue(is_numeric($matches[1]));

        $file = File::find($matches[1])->one();
        $this->checkUnprotectedTmpFile($file, -1, Yii::$app->fileManager->getOwnerType('news.gallery'));

        $model = new News([
            'title' => 'test',
            'gallery' => ['id' . $file->id => 'test']
        ]);

        $this->assertTrue($model->save());

        $files = $model->getFiles('gallery');

        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $this->checkUnprotectedNotTmpFile($file, $model->id, Yii::$app->fileManager->getOwnerType('news.gallery'));
        }

        return ['files' => $files, 'model' => $model];
    }

    public function testUpload()
    {
        $this->baseUpload();
    }

    public function testGalleryUpload()
    {
        $this->baseGalleryUpload();
    }

    public function testSaveAfterUpload()
    {
        $response = $this->runAction($config = [
            'modelName' => News::className(),
            'attribute' => 'preview',
            'inputName' => 'file',
            'type' => 'image',
            'saveAfterUpload' => true
        ]);

        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('path', $response);

        $file = File::find($response['id'])->one();
        $this->checkUnprotectedNotTmpFile($file, 0, Yii::$app->fileManager->getOwnerType('news.preview'));
    }

    public function testSaveAfterUploadWithOwnerId()
    {
        $response = $this->runAction($config = [
            'modelName' => News::className(),
            'attribute' => 'preview',
            'inputName' => 'file',
            'type' => 'image',
            'saveAfterUpload' => true,
            'ownerId' => 100
        ]);

        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('path', $response);

        $file = File::find($response['id'])->one();
        $this->checkUnprotectedNotTmpFile($file, 100, Yii::$app->fileManager->getOwnerType('news.preview'));
    }

    public function testResultName()
    {
        $response = $this->runAction($config = [
            'modelName' => News::className(),
            'attribute' => 'preview',
            'inputName' => 'file',
            'type' => 'image',
            'resultName' => 'customName',
        ]);

        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('customName', $response);
    }

    public function testSaveFileId()
    {
        $response = $this->runAction($config = [
            'modelName' => News::className(),
            'attribute' => 'photo_id',
            'inputName' => 'file',
            'type' => 'image'
        ]);

        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('path', $response);

        $file = File::find($response['id'])->one();
        $this->checkUnprotectedTmpFile($file, -1, Yii::$app->fileManager->getOwnerType('news.photo_id'));

        $model = new News([
            'title' => 'test',
            'photo_id' => $file->id
        ]);

        $this->assertTrue($model->save());

        $file = File::find($file->id)->one();
        $this->checkUnprotectedNotTmpFile($file, $model->id, Yii::$app->fileManager->getOwnerType('news.photo_id'));
        $this->assertTrue($model->photo_id === $file->id);
    }

    public function testDeleteFile()
    {
        extract($this->baseUpload());

        $file->delete();
        $this->assertFileNotExists($file->path(true));
    }

    public function testDeleteModel()
    {
        extract($this->baseUpload());

        $model->delete();
        $this->assertFileNotExists($file->path(true));

        $file = File::find($file->id)->one();
        $this->assertNull($file);
    }

    public function testSetEmptyFile()
    {
        extract($this->baseUpload());

        $model->preview = '';
        $model->save();

        $file = File::find($file->id)->one();
        $this->assertNull($file);
    }

    public function testSetEmptyGallery()
    {
        extract($this->baseGalleryUpload());

        $model->gallery = [];
        $model->save();

        $files = $model->getFiles('gallery');
        $this->assertCount(0, $files);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage The "modelName" attribute must be set.
     */
    public function testEmptyModelName()
    {
        $response = $this->runAction($config = [
            'attribute' => 'preview',
            'inputName' => 'file',
            'type' => 'image'
        ]);
    }

    public function testWrongInputName()
    {
        $response = $this->runAction($config = [
            'modelName' => News::className(),
            'attribute' => 'preview',
            'inputName' => 'fail',
            'type' => 'image'
        ]);

        $this->assertCount(1, $response);
        $this->assertTrue(isset($response['error']));
    }
}
