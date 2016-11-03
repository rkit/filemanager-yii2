<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use tests\data\models\File;

class SingleUploadTest extends BaseTest
{
    private $modelClass = 'tests\data\models\News';

    public function setUp()
    {
        parent::setUp();
    }

    public function testUpload()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300',
        ]);

        $this->assertCount(2, $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('path', $response);

        $file = File::findOne($response['id']);
        $this->assertInstanceOf(File::className(), $file);

        $path = $model->filePath('image', $file);
        $this->assertFileExists($path);
    }

    public function testUploadAndBind()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300',
        ]);

        $model->image = $response['id'];
        $model->save();

        $file = $model->file('image');

        $this->assertInstanceOf(File::className(), $file);
        $this->assertFileExists($model->filePath('image'));
        $this->assertContains($model->image, $model->filePath('image'));
        $this->assertEquals($file->id, $response['id']);
    }

    public function testSaveFilePath()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300',
        ]);

        $model->image = $response['id'];
        $model->save();

        $this->assertContains($model->image, $model->filePath('image'));
    }

    public function testSaveFileId()
    {
        $model = $this->createObject($this->modelClass, [
            'saveFilePathInAttribute' => false,
            'saveFileIdInAttribute' => true,
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300',
        ]);

        $model->image = $response['id'];
        $model->save();

        $this->assertEquals($model->image, $model->file('image')->id);
    }

    public function testReUpload()
    {
        $model = $this->createObject($this->modelClass);
        $response1 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300',
        ]);

        $model->image = $response1['id'];
        $model->save();

        $file1 = $model->file('image');
        $file1Path = $model->filePath('image');

        $this->assertEquals($file1->id, $response1['id']);

        $response2 = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-500',
        ]);

        $model->image = $response2['id'];
        $model->save();

        $file2 = $model->file('image');
        $file2Path = $model->filePath('image');

        $this->assertEquals($file2->id, $response2['id']);
        $this->assertFileExists($file2Path);
        $this->assertFileNotExists($file1Path);
        $this->assertNull(File::findOne($response1['id']));
    }

    public function testUpdateLinks()
    {
        $model = $this->createObject($this->modelClass, [
            'extraFields' => function () {
                return [
                    'type' => 2,
                    'position' => 1,
                ];
            }
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $fields = $model->fileExtraFields('image');
        $this->assertEquals($fields['position'], 1);

        $model = $this->createObject($this->modelClass, [
            'extraFields' => function () {
                return [
                    'type' => 2,
                    'position' => 2,
                ];
            }
        ], $model->id);

        $model->image = $response['id'];
        $model->title = 'tester';
        $model->save();

        $fields = $model->fileExtraFields('image');
        $this->assertEquals($fields['position'], 2);
    }

    public function testUpdateFiles()
    {
        $model = $this->createObject($this->modelClass, [
            'updateFile' => function ($file) {
                $file->title = 'test';
                return $file;
            }
        ]);

        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $file = File::findOne($response['id']);

        $this->assertInstanceOf(File::className(), $file);
        $this->assertEquals($file->title, 'test');
    }

    public function testDeleteModel()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();
        $model->delete();

        $this->assertNull($model->file('image'));
        $this->assertFileNotExists($model->filePath('image'));
    }

    public function testEmptyFilePath()
    {
        $model = $this->createObject($this->modelClass);
        $model->image = '';
        $model->save();

        $this->assertNull($model->file('image'));
        $this->assertEmpty($model->image);
    }

    public function testClearField()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $model->image = '';
        $model->save();

        $this->assertNull(File::findOne($response['id']));
        $this->assertEmpty($model->image);
    }

    public function testNotChanged()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $path = $model->image;

        // repeat
        $model->save();

        $file = File::findOne($response['id']);

        $this->assertInstanceOf(File::className(), $file);
        $this->assertEquals($response['id'], $model->file('image')->id);
        $this->assertEquals($path, $model->image);
    }

    public function testWrongFilePath()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);

        $model->image = $response['id'];
        $model->save();

        $model->image = 'test';
        $model->save();

        $file = File::findOne($response['id']);

        $this->assertInstanceOf(File::className(), $file);
        $this->assertEquals($response['id'], $model->file('image')->id);
    }

    public function testCustomResultFieldId()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300',
            'resultFieldId' => 'customFieldId',
        ]);

        $this->assertArrayHasKey('customFieldId', $response);
    }

    public function testCustomResultFieldPath()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-300',
            'resultFieldPath' => 'customFieldPath',
        ]);

        $this->assertArrayHasKey('customFieldPath', $response);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage rkit\filemanager\actions\UploadAction::$modelClass or
     */
    public function testEmptyModelClass()
    {
        $this->runUploadAction([
            'attribute' => 'image',
            'inputName' => 'file-300'
        ]);
    }

    public function testWrongInputName()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'fail'
        ]);

        $this->assertCount(1, $response);
        $this->assertArrayHasKey('error', $response);
    }

    public function testWrongImageSize()
    {
        $model = $this->createObject($this->modelClass);
        $response = $this->runUploadAction([
            'modelObject' => $model,
            'attribute' => 'image',
            'inputName' => 'file-100'
        ]);

        $this->assertCount(1, $response);
        $this->assertArrayHasKey('error', $response);
        $this->assertContains('The image "100x100.png" is too small', $response['error']);
    }
}
