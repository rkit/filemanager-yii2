<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;
use rkit\filemanager\models\File;

/**
 * File Manager
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 * @since 1.0
 */
class FileManager extends Component
{
    /**
     * @var string Directory to upload files protected, not accessible from the web
     */
    public $uploadDirProtected = '@runtime';
    /**
     * @var string Directory to upload files, accessible from the web
     */
    public $uploadDirUnprotected = '@app/web';
    /**
     * @var string Public path to files
     */
    public $publicPath = 'uploads';
    /**
     * @var array Type of owner in format: title:string => type:int
     */
    public $ownerTypes = [];

    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }

    public function getOwnerType($ownerType)
    {
        if (!isset($this->ownerTypes[$ownerType])) {
            throw new InvalidParamException('This type `' . $ownerType . '` is not found');
        }

        return $this->ownerTypes[$ownerType];
    }

    /**
     * Create file from uploader (UploadedFile)
     *
     * @param UploadedFile $data
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $saveAfterUpload Save the file immediately after upload
     * @param bool $protected File is protected?
     * @return File|bool
     */
    public static function createFromUploader(
        $data,
        $ownerId = -1,
        $ownerType = -1,
        $saveAfterUpload = false,
        $protected = false
    ) {
        $pathInfo = pathinfo($data->name);
        $file = new File([
            'tmp' => true,
            'owner_id' => $ownerId,
            'owner_type' => $ownerType,
            'size' => $data->size,
            'mime' => $data->type,
            'title' => $pathInfo['filename'],
            'name' => File::generateName($pathInfo['extension']),
            'protected' => $protected
        ]);

        return $file->saveToTmp($data->tempName, $saveAfterUpload);
    }

    /**
     * Create file from path
     *
     * @param string $path
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $saveAfterUpload Save the file immediately after upload
     * @param bool $protected File is protected?
     * @return File|bool
     */
    public static function createFromPath(
        $path,
        $ownerId = -1,
        $ownerType = -1,
        $saveAfterUpload = false,
        $protected = false
    ) {
        if (file_exists($path)) {
            $pathInfo = pathinfo($path);
            $file = new File([
                'tmp' => true,
                'owner_id' => $ownerId,
                'owner_type' => $ownerType,
                'size' => filesize($path),
                'mime' => FileHelper::getMimeType($path),
                'title' => $pathInfo['filename'],
                'name' => File::generateName($pathInfo['extension']),
                'protected' => $protected
            ]);

            return $file->saveToTmp($path, $saveAfterUpload);
        }

        return false;
    }

    /**
     * Registers translator
     */
    public function registerTranslations()
    {
        if (!isset(\Yii::$app->i18n->translations['filemanager-yii2'])) {
            \Yii::$app->i18n->translations['filemanager-yii2'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@vendor/rkit/filemanager-yii2/messages',
                'sourceLanguage' => 'en',
            ];
        }
    }
}
