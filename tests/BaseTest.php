<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use Yii;
use yii\helpers\FileHelper;
use tests\data\Controller;
use tests\data\News;
use rkit\filemanager\actions\UploadAction;
use rkit\filemanager\models\File;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $storage = 'rkit\filemanager\storages\LocalStorage';

    public $files = [];

    protected function setUp()
    {
        $this->storage = new $this->storage();
        $this->prepareFiles();
    }

    protected function tearDown()
    {
        File::deleteAll();
        News::deleteAll();

        $fileManager = Yii::$app->fileManager;

        FileHelper::removeDirectory(Yii::getAlias($fileManager->uploadDirProtected));
        FileHelper::removeDirectory(Yii::getAlias($fileManager->uploadDirUnprotected));
        FileHelper::removeDirectory(Yii::getAlias('@tests/data/files/tmp'));

        unset($_FILES);
    }

    private function prepareFiles()
    {
        $this->files = require Yii::getAlias('@tests/data/files.php');
        FileHelper::createDirectory(Yii::getAlias('@tests/data/files/tmp'));

        $_FILES = [];
        foreach ($this->files as $inputName => $fileInfo) {
            $_FILES[$inputName] = [
                'name' => $fileInfo['name'] . '.' . $fileInfo['ext'],
                'type' => $fileInfo['type'],
                'size' => $fileInfo['size'],
                'tmp_name' => $this->prepareFile($inputName),
                'error' => 0
            ];
        }
    }

    /**
     * Prepare a file for test
     *
     * @param string $fileIndex See property $files
     * @param string $prefix Prefix for a file
     * @return string Path to file
     */
    protected function prepareFile($fileIndex, $prefix = 'copy')
    {
        $file = $this->files[$fileIndex];
        $origFile = Yii::getAlias(
            '@tests/data/files/' . $file['name'] . '.' . $file['ext']
        );
        $copyFile = Yii::getAlias(
            '@tests/data/files/tmp/' . $file['name'] . '_' . $prefix . '.' . $file['ext']
        );

        copy($origFile, $copyFile);

        return $copyFile;
    }

    /**
     * Runs the upload action
     *
     * @param array $config
     * @return mixed The result of the action
     */
    protected function runUploadAction($config)
    {
        $action = new UploadAction('upload', new Controller('test', Yii::$app), $config);
        return $action->run();
    }

    /**
     * Test tmp a file
     *
     * @param string $file
     * @param string $ownerId
     * @param string $ownerType
     * @return void
     */
    protected function checkTmpFile($file, $ownerId, $ownerType)
    {
        $this->assertTrue(is_object($file));
        $this->assertFileExists($file->getStorage()->path(true));
        $this->assertTrue($file->isTmp());
        $this->assertTrue((bool)$file->tmp);
        $this->assertTrue($file->owner_id === $ownerId);
        $this->assertTrue($file->owner_type === $ownerType);
    }

    /**
     * Test not tmp a file
     *
     * @param string $file
     * @param string $ownerId
     * @param string $ownerType
     * @return void
     */
    protected function checkNotTmpFile($file, $ownerId, $ownerType)
    {
        $this->assertTrue(is_object($file));
        $this->assertFileExists($file->getStorage()->path(true));
        $this->assertFalse($file->isTmp());
        $this->assertTrue(!(bool)$file->tmp);
        $this->assertTrue($file->owner_id === $ownerId);
        $this->assertTrue($file->owner_type === $ownerType);
    }

    /**
     * Test response after uploading the file
     *
     * @param array $response
     * @return array $response
     */
    protected function checkUploadFileResponse($response)
    {
        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('path', $response);

        return $response;
    }

    /**
     * Test response after uploading the gallery
     *
     * @param array $response
     * @return array $response
     */
    protected function checkUploadGalleryResponse($response)
    {
        preg_match('/News\[(.*?)\]\[files\]\[(.*?)\]/', $response, $matches);

        $this->assertTrue(is_string($response));
        $this->assertTrue(isset($matches[2]));
        $this->assertTrue(is_numeric($matches[2]));

        $matches[1] = $matches[2];

        return $matches;
    }

    /**
     * Upload and test a file
     *
     * @param array $config see Properties UploadAction
     * @param bool $saveFilePath If save 'path' in current model
     * @return array $file and $model
     */
    protected function uploadFileAndBindToModel($config, $saveFilePath = true)
    {
        $response = $this->runUploadAction($config);
        $response = $this->checkUploadFileResponse($response);

        $file = File::findOne($response['id']);
        $file->setStorage($this->storage);

        $model = new News(['title' => 'test', $config['attribute'] => $file->id]);
        $ownerType = $model->getFileOwnerType($config['attribute']);

        $this->assertTrue($model->save());

        $file = File::findOne($file->id);
        $file->setStorage($this->storage);

        return [$file, $model];
    }

    /**
     * Upload and test a gallery
     *
     * @param array $config see Properties UploadAction
     * @return array $files and $model
     */
    protected function uploadMultipleAndBindToModel($config)
    {
        $response = $this->runUploadAction($config);
        $response = $this->checkUploadGalleryResponse($response);

        $file = File::findOne($response[1]);
        $file->setStorage($this->storage);

        $model = new News([
            'title' => 'test',
            $config['attribute'] => ['files' => [$file->id => 'test']]
        ]);
        $ownerType = $model->getFileOwnerType($config['attribute']);

        $this->assertTrue($model->save());

        $files = $model->getFiles($config['attribute']);
        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $file->setStorage($this->storage);
        }

        return [$files, $model];
    }
}
