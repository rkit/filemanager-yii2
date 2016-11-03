<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager;

use Yii;
use yii\base\Component;

/**
 * Component of FileManager
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 */
class FileManager extends Component
{
    /**
     * @var string Session variable name
     */
    public $sessionName = 'filemanager.uploads';

    /**
     * @internal
     */
    public function init()
    {
        parent::init();
        $this->registerTranslations();
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
