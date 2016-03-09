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
 * Component of FileManager
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
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
     * @internal
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();
    }

    /**
     * Get owner type
     *
     * @param string $ownerType The type of the owner
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
     * Registers translator
     * @internal
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
