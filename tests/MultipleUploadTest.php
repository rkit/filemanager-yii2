<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use Yii;
use tests\data\models\File;

class MultipleUploadTest extends BaseTest
{
    private $modelClass = 'tests\data\models\News';

    public function setUp()
    {
        parent::setUp();
    }

    public function testUpload()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('path', $response);

        $file = File::findOne($response['id']);
        $this->assertInstanceOf(File::className(), $file);

        $path = $model->filePath('gallery', $file);
        $this->assertFileExists($path);
    }

    public function testUploadWithTemplate()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true,
            'template' => '@tests/data/views/gallery-item.php',
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        preg_match('/value=\"(.*?)\"/', $response, $matches);

        $this->assertTrue(is_string($response));
        $this->assertCount(2, $matches);
        $this->assertTrue(is_numeric($matches[1]));
    }

    public function testUploadAndBind()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true,
            'rules.maxFiles' => 2,
        ]);

        $response1 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $response2 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $model->gallery = [$response1['id'], $response2['id']];
        $model->save();

        $files = $model->allFiles('gallery');
        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $this->assertInstanceOf(File::className(), $file);
            $this->assertFileExists($model->filePath('gallery', $file));
        }
    }

    public function testAddFile()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true,
            'rules.maxFiles' => 2,
        ]);

        $response1 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $model->gallery = [$response1['id']];
        $model->save();

        $response2 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $model->gallery = [$response1['id'], $response2['id']];
        $model->save();

        $files = $model->allFiles('gallery');
        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $this->assertInstanceOf(File::className(), $file);
            $this->assertFileExists($model->filePath('gallery', $file));
        }
    }

    public function testUpdateLinks()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true,
            'extraFields' => function () {
                return [
                    'type' => 2,
                    'position' => 1,
                ];
            }
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300'
        ]);

        $model->gallery = [$response['id']];
        $model->save();

        $fields = $model->fileExtraFields('gallery');
        $this->assertEquals($fields[$response['id']]['position'], 1);

        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true,
            'extraFields' => function () {
                return [
                    'type' => 2,
                    'position' => 2,
                ];
            }
        ], $model->id);

        $model->gallery = [$response['id']];
        $model->title = 'tester';
        $model->save();

        $fields = $model->fileExtraFields('gallery');
        $this->assertEquals($fields[$response['id']]['position'], 2);
    }

    public function testMaxFiles()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true,
            'rules.maxFiles' => 1,
        ]);

        $response1 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);
        $response2 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $model->gallery = [$response1['id'], $response2['id']];
        $model->save();

        $files = $model->allFiles('gallery');
        $this->assertCount(1, $files);
    }

    public function testClearFiles()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true,
            'rules.maxFiles' => 2,
        ]);

        $response1 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);
        $response2 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $model->gallery = [$response1['id'], $response2['id']];
        $model->save();

        $files = $model->allFiles('gallery');
        $this->assertCount(2, $files);

        $model->gallery = [];
        $model->save();

        $this->assertCount(0, $model->allFiles('gallery'));
    }

    public function testDeleteWrongItem()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'gallery',
            'inputName' => 'file-300',
        ]);

        $model->gallery = [$response['id'], 9999];
        $model->save();

        $this->assertCount(1, $model->allFiles('gallery'));
    }

    public function testDeleteWrongGallery()
    {
        $model = $this->createObject($this->modelClass, [
            'field' => 'gallery',
            'multiple' => true
        ]);

        $model->gallery = [9999];
        $model->save();

        $this->assertCount(0, $model->allFiles('gallery'));
    }
}
