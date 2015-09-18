<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
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
    public $files = [
        'file-100' => ['name' => '100x100', 'size' => 293, 'type' => 'image/png', 'ext' => 'png'],
        'file-300' => ['name' => '300x300', 'size' => 1299, 'type' => 'image/png', 'ext' => 'png'],
        'file-500' => ['name' => '500x500', 'size' => 1543, 'type' => 'image/png', 'ext' => 'png']
    ];

    protected function setUp()
    {
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

    protected function tearDown()
    {
        File::deleteAll();
        News::deleteAll();

        FileHelper::removeDirectory(Yii::getAlias(Yii::$app->fileManager->uploadDirProtected));
        FileHelper::removeDirectory(Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected));

        unset($_FILES);
        foreach ($this->files as $inputName => $fileInfo) {
            @unlink(Yii::getAlias('@tests/data/files/' . $fileInfo['name'] . '_copy.' . $fileInfo['ext']));
        }
    }

    /**
     * Runs the action
     *
     * @param array $config
     * @return mixed The result of the action
     */
    protected function runAction($config)
    {
        $action = new UploadAction('upload', new Controller('test', Yii::$app), $config);
        return $action->run();
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
        $origFile = Yii::getAlias('@tests/data/files/' . $file['name'] . '.' . $file['ext']);
        $copyFile = Yii::getAlias('@tests/data/files/' . $file['name'] . '_' . $prefix . '.' . $file['ext']);
        copy($origFile, $copyFile);

        return $copyFile;
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
        $this->assertFileExists($file->path(true));
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
        $this->assertFileExists($file->path(true));
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
        preg_match('/News\[(.*?)\]\[id(.*?)\]/', $response, $matches);

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
    protected function uploadFile($config, $saveFilePath = true)
    {
        $ownerType = Yii::$app->fileManager->getOwnerType('news.' . $config['attribute']);

        $response = $this->runAction($config);
        $response = $this->checkUploadFileResponse($response);

        $file = File::findOne($response['id']);
        $this->checkTmpFile($file, -1, Yii::$app->fileManager->getOwnerType('news.' . $config['attribute']));

        $model = new News(['title' => 'test', $config['attribute'] => $file->id]);
        $this->assertTrue($model->save());

        $file = File::findOne($file->id);
        $this->checkNotTmpFile($file, $model->id, $ownerType);

        if ($saveFilePath) {
            $this->assertContains($model->$config['attribute'], $file->path());
        } else {
            $this->assertTrue($model->$config['attribute'] === $file->id);
        }

        return ['file' => $file, 'model' => $model];
    }

    /**
     * Upload and test a gallery
     *
     * @param array $config see Properties UploadAction
     * @return array $files and $model
     */
    protected function uploadGallery($config)
    {
        $ownerType = Yii::$app->fileManager->getOwnerType('news.' . $config['attribute']);

        $response = $this->runAction($config);
        $response = $this->checkUploadGalleryResponse($response);

        $file = File::findOne($response[1]);
        $this->checkTmpFile($file, -1, $ownerType);

        $model = new News(['title' => 'test', $config['attribute'] => ['id' . $file->id => 'test']]);
        $this->assertTrue($model->save());

        $files = $model->getFiles($config['attribute']);
        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $this->checkNotTmpFile($file, $model->id, $ownerType);
        }

        return ['files' => $files, 'model' => $model];
    }
}
