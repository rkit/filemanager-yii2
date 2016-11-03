<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use Yii;
use yii\helpers\FileHelper;
use tests\data\controllers\Controller;
use rkit\filemanager\actions\UploadAction;

abstract class BaseTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        Yii::$app->getDb()->open();
        return $this->createDefaultDBConnection(\Yii::$app->getDb()->pdo);
    }

    /**
     * @inheritdoc
     */
    public function getDataSet()
    {
        $data = require Yii::getAlias('@tests/data/fixtures/file.php');
        return new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet($data);
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function setUpBeforeClass()
    {
        FileHelper::createDirectory(Yii::getAlias('@tests/data/files/tmp'));
        $_FILES = require Yii::getAlias('@tests/data/files.php');
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function tearDownAfterClass()
    {
        FileHelper::removeDirectory(Yii::getAlias('@tests/tmp/public'));
        FileHelper::removeDirectory(Yii::getAlias('@tests/data/files/tmp'));

        unset($_FILES);
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function tearDown()
    {
        $db = Yii::$app->getDb()->createCommand();
        $db->truncateTable('news')->execute();
        $db->truncateTable('news_files')->execute();
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function createTmpFile($fileName)
    {
        if (!isset($_FILES[$fileName])) {
            return false;
        }

        $file = $_FILES[$fileName];
        copy(Yii::getAlias('@tests/data/files/' . $file['name']), $file['tmp_name']);

        return $file['tmp_name'];
    }

    /**
     * Runs the upload action
     *
     * @param array $config
     * @return mixed The result of the action
     */
    public function runUploadAction($config)
    {
        $this->createTmpFile($config['inputName']);

        $action = new UploadAction('upload', new Controller('test', Yii::$app), $config);
        return $action->run();
    }

    public function createObject($modelClass, $options = [], $id = null)
    {
          $options;
          $behavior = require Yii::getAlias('@tests/data/behavior.php');

          $model = $id ? $modelClass::findOne($id) : new $modelClass;
          $model->attachBehavior('fileManager', $behavior);

          return $model;
    }
}
