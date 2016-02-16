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
    private $decoder;

    public function init()
    {
        parent::init();

        $this->setDecoder(new Decoder());
        $this->registerTranslations();
    }

    /**
     * Get owner type
     *
     * @param string $ownerType
     * @return void
     * @throws InvalidParamException
     */
    public function getOwnerType($ownerType)
    {
        if (!isset($this->ownerTypes[$ownerType])) {
            throw new InvalidParamException('This type `' . $ownerType . '` is not found');
        }

        return $this->ownerTypes[$ownerType];
    }

    /**
     * Set Decoder
     *
     * @return void
     */
    public function setDecoder($decoder)
    {
        $this->decoder = $decoder;
    }

    /**
     * Set Decoder
     *
     * @return void
     */
    public function getDecoder()
    {
        return $this->decoder;
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
