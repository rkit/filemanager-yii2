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

class FileUploadTest extends BaseTest
{
    public function testUploadUnprotectedFile()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $this->assertTrue($file->isUnprotected());
        $this->assertContains(Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected), $file->path(true));
    }

    public function testUploadProtectedFile()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false));

        $this->assertTrue($file->isProtected());
        $this->assertContains(Yii::getAlias(Yii::$app->fileManager->uploadDirProtected), $file->path(true));
    }

    public function testSaveAfterUpload()
    {
        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'saveAfterUpload' => true,
            'ownerId' => 0
        ]);

        $file = File::findOne($response['id']);
        $this->checkNotTmpFile($file, 0, Yii::$app->fileManager->getOwnerType('news.image_path'));
    }

    public function testSaveAfterUploadWithOwnerId()
    {
        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'saveAfterUpload' => true,
            'ownerId' => 100
        ]);

        $file = File::findOne($response['id']);
        $this->checkNotTmpFile($file, 100, Yii::$app->fileManager->getOwnerType('news.image_path'));
    }

    public function testResultName()
    {
        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300',
            'resultName' => 'customName',
        ]);

        $this->assertArrayHasKey('customName', $response);
    }

    public function testSaveFileId()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false));

        $this->assertTrue(is_numeric($model->image_id));
    }

    public function testDeleteFile()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $file->delete();
        $this->assertFileNotExists($file->path(true));
    }

    public function testDeleteModel()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $model->delete();
        $this->assertFileNotExists($file->path(true));

        $file = File::findOne($file->id);
        $this->assertNull($file);
    }

    public function testSetEmptyFile()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

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
        $this->runAction([
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]);
    }

    public function testWrongInputName()
    {
        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'fail'
        ]);

        $this->assertCount(1, $response);
        $this->assertTrue(isset($response['error']));
    }

    public function testWrongImageSize()
    {
        $response = $this->runAction([
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
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $model->image_path = 'test';
        $model->save();

        $this->assertTrue($model->image_path === $file->path());
    }

    public function testWrongFileId()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_id',
            'inputName' => 'file-300'
        ], false));

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
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $response = $this->runAction([
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
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500',
            'ownerId'   => 100,
            'saveAfterUpload' => true
        ]);

        $model->image_path = $response['id'];
        $model->save();

        $file = File::findOne($response['id']);

        $this->assertFalse($file->path() === $model->image_path);
    }

    public function testSaveNotTmpFile()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500',
            'ownerId' => $model->id,
            'saveAfterUpload' => true
        ]);

        $model->image_path = $response['id'];
        $model->save();

        $file = File::findOne($response['id']);

        $this->assertFalse($file->path() === $model->image_path);
    }

    public function testFailSaveFile()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $response = $this->runAction([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-500'
        ]);

        $file = File::findOne($response['id']);
        unlink($file->path(true));

        $model->image_path = $response['id'];
        $model->save();

        $this->assertFalse($file->path() === $model->image_path);
    }

    public function testNotTmpUnprotectedCreateFromPath()
    {
        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = Yii::$app->fileManager->create($file, 100, 200, true, false);

        $this->assertTrue(is_object($file));
        $this->assertFalse($file->isTmp());
        $this->assertTrue($file->isUnprotected());
        $this->assertFileExists($file->path(true));
    }

    public function testNotTmpProtectedCreateFromPath()
    {
        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = Yii::$app->fileManager->create($file, 100, 200, true, true);

        $this->assertTrue(is_object($file));
        $this->assertFalse($file->isTmp());
        $this->assertTrue($file->isProtected());
        $this->assertFileExists($file->path(true));
    }

    public function testTmpUnprotectedCreateFromPath()
    {
        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = Yii::$app->fileManager->create($file, 100, 200, false, false);

        $this->assertTrue(is_object($file));
        $this->assertTrue($file->isTmp());
        $this->assertTrue($file->isUnprotected());
        $this->assertFileExists($file->path(true));
    }

    public function testTmpProtectedCreateFromPath()
    {
        $file = $this->prepareFile('file-100', 'test_create_from_path');
        $file = Yii::$app->fileManager->create($file, 100, 200, false, true);

        $this->assertTrue(is_object($file));
        $this->assertTrue($file->isTmp());
        $this->assertTrue($file->isProtected());
        $this->assertFileExists($file->path(true));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unable to create from `/test/fail.jpg`
     */
    public function testFailCreateFromPath()
    {
        $file = Yii::$app->fileManager->create('/test/fail.jpg', 100, 200, true, false);
    }

    public function testRealPath()
    {
        extract($this->uploadFile([
            'modelName' => News::className(),
            'attribute' => 'image_path',
            'inputName' => 'file-300'
        ]));

        $this->assertFileExists($model->getFileRealPath('image_path'));
    }
}
