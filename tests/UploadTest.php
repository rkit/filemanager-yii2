<?php

namespace rkit\filemanager\tests;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use rkit\filemanager\tests\data\Controller;
use rkit\filemanager\tests\data\News;
use rkit\filemanager\actions\UploadAction;
use rkit\filemanager\models\File;

class UploadTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->prepareFiles();
    }

    protected function tearDown()
    {
        unset($_FILES);
        File::deleteAll();
        News::deleteAll();
        FileHelper::removeDirectory(Yii::$app->fileManager->uploadDirProtected);
        FileHelper::removeDirectory(Yii::$app->fileManager->uploadDirUnprotected);
    }

    protected function prepareFiles()
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

    public function testUpload()
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

    public function testGallery()
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
    }

    // @todo test ResizeAferUpload
    // @todo test ProtectedUpload
    // @todo test Resize
}
