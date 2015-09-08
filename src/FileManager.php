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
use yii\imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use rkit\filemanager\models\File;

/**
 * File Manager.
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 * @since 1.0
 */
class FileManager extends Component
{
    /**
     * @var string Directory to upload files protected, not accessible from the web.
     */
    public $uploadDirProtected = '@runtime';
    /**
     * @var string Directory to upload files, accessible from the web.
     */
    public $uploadDirUnprotected = '@app/web';
    /**
     * @var string Public path to files.
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
     * Resize.
     *
     * @param string $file
     * @param int $width
     * @param int $height
     * @param bool $ratio
     * @param bool $replace
     * @param bool $isProtected Unprotected or Protected.
     * @return string
     */
    public function resize(
        $file,
        $width,
        $height,
        $ratio = false,
        $replace = false,
        $isProtected = false
    ) {
        $uploadDir = $isProtected ? 'uploadDirProtected' : 'uploadDirUnprotected';
        $uploadDir = Yii::getAlias(Yii::$app->fileManager->$uploadDir);

        if (!file_exists($uploadDir . $file)) {
            return $file;
        }

        if ($replace) {
            $thumb = $file;
        } else {
            $thumb = File::generateThumbName($file, $width, $height);
            if (file_exists($uploadDir . $thumb)) {
                return $thumb;
            }
        }

        $imagine = imagine\Image::getImagine();
        $image = $imagine->open($uploadDir . $file);
        $image = $this->resizeMagic($image, $width, $height, $ratio);
        $image->save($uploadDir . $thumb, ['jpeg_quality' => 100, 'png_compression_level' => 9]);

        return $thumb;
    }

    /**
     * Magick resizing method.
     *
     * @param imagine\Image $image
     * @param int $width
     * @param int $height
     * @param bool $ratio
     * @return imagine\Image
     */
    private function resizeMagic($image, $width, $height, $ratio)
    {
        if ($width < 1 || $height < 1) {
            if ($height < 1) {
                $image = $image->resize($image->getSize()->widen($width));
            } else {
                $image = $image->resize($image->getSize()->heighten($height));
            }

        } else {
            $size = new Box($width, $height);
            $mode = $ratio ? ImageInterface::THUMBNAIL_INSET : ImageInterface::THUMBNAIL_OUTBOUND;
            $image = $image->thumbnail($size, $mode);
        }

        return $image;
    }

    /**
     * Registers translator.
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
