<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;

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
    /**
     * @var Decoder
     */
    public static $decoder = null;

    public function init()
    {
        parent::init();

        $this->setDecoder();
        $this->registerTranslations();
    }

    /**
     * Get owner type
     *
     * @param string $ownerType
     * @return void
     */
    public function getOwnerType($ownerType)
    {
        if (!isset($this->ownerTypes[$ownerType])) {
            throw new InvalidParamException('This type `' . $ownerType . '` is not found');
        }

        return $this->ownerTypes[$ownerType];
    }

    /**
     * It is from uploader?
     *
     * @param mixed $data
     * @return bool
     */
    private function isFromUploader($data)
    {
        return $data instanceof yii\web\UploadedFile;
    }

    /**
     * Create new file from mixed data
     *
     * @param mixed $data
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $saveAfterUpload Save the file immediately after upload
     * @param bool $protected File is protected?
     * @return \rkit\filemanager\models\File|bool
     */
    public function create($data, $ownerId = -1, $ownerType = -1, $saveAfterUpload = false, $protected = false)
    {
        switch ($data) {
            case $this->isFromUploader($data):
                return $this->decoder->createFromUploader($data, $ownerId, $ownerType, $saveAfterUpload, $protected);
            default:
                return $this->decoder->createFromPath($data, $ownerId, $ownerType, $saveAfterUpload, $protected);
        }
    }

    /**
     * Set Decoder
     *
     * @return void
     */
    public function setDecoder()
    {
        $this->decoder = new Decoder();
    }

    /**
     * Registers translator
     *
     * @return void
     */
    public function registerTranslations()
    {
        if (!isset(\Yii::$app->i18n->translations['filemanager-yii2'])) {
            \Yii::$app->i18n->translations['filemanager-yii2'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@vendor/rkit/filemanager-yii2/src/messages',
                'sourceLanguage' => 'en',
            ];
        }
    }
}
