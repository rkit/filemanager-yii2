<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager\helpers;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * Formatting of validation rules
 */
class FormatValidation
{
    /**
     * Get description for max size of file
     *
     * @param int $rules
     * @return string
     */
    private static function getMaxSizeDescription($rules)
    {
        $maxSize = Yii::$app->formatter->asShortSize($rules);
        return Yii::t('filemanager-yii2', 'Max. file size') . ': ' . $maxSize . ' ';
    }

    /**
     * Get description for extensions of file
     *
     * @param array $rules
     * @return string
     */
    private static function getExtensionDescription($rules)
    {
        $extensions = strtoupper(implode(', ', $rules));
        return Yii::t('filemanager-yii2', 'File types') . ': ' . $extensions . ' ';
    }

    /**
     * Get description for size of image
     *
     * @param array $rules
     * @return string
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private static function getImageSizeDescription($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        $text = '';
        if (self::isImageWithStrictSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Image size') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif (self::isImageWithMinAndMaxSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= Yii::t('filemanager-yii2', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif (self::isImageWithMinSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= self::prepareImageSizeDescription($rules, ['minWidth', 'minHeight']);
        } elseif (self::isImageWithMaxSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
            $text .= self::prepareImageSizeDescription($rules, ['maxWidth', 'maxHeight']);
        } else {
            $text .= self::prepareImageSizeDescription($rules);
        }

        $text = mb_substr($text, 0, -1);
        $text = str_replace(';', '<br>', $text);

        return $text;
    }

    /**
     * This rules for image with strict size
     *
     * @param array $rules
     * @return bool
     */
    private static function isImageWithStrictSize($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        return count($rules) == 4 && ($maxWidth == $minWidth && $maxHeight == $minHeight);
    }

    /**
     * This rules for image with min and max size
     *
     * @param array $rules
     * @return bool
     */
    private static function isImageWithMinAndMaxSize($rules)
    {
        return count($rules) == 4;
    }

    /**
     * This rules for image with min size
     *
     * @param array $rules
     * @return bool
     */
    private static function isImageWithMinSize($rules)
    {
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        return (count($rules) == 2 || count($rules) == 3) && $minWidth && $minHeight;
    }

    /**
     * This rules for image with max size
     *
     * @param array $rules
     * @return bool
     */
    private static function isImageWithMaxSize($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');

        return (count($rules) == 2 || count($rules) == 3) && $maxWidth && $maxHeight;
    }

    /**
     * Prepare description for size of image
     *
     * @param array $rules
     * @return string
     */
    private static function prepareImageSizeDescription($rules, $exclude = [])
    {
        foreach ($exclude as $item) {
            unset($rules[$item]);
        }

        $text = '';
        foreach ($rules as $rule => $value) {
            switch ($rule) {
                case 'minWidth':
                    $text .= Yii::t('filemanager-yii2', 'Min. width') . ' ' . $value . 'px;';
                    break;
                case 'minHeight':
                    $text .= Yii::t('filemanager-yii2', 'Min. height') . ' ' . $value . 'px;';
                    break;
                case 'maxWidth':
                    $text .= Yii::t('filemanager-yii2', 'Max. width') . ' ' . $value . 'px;';
                    break;
                case 'maxHeight':
                    $text .= Yii::t('filemanager-yii2', 'Max. height') . ' ' . $value . 'px;';
                    break;
            }
        }

        return $text;
    }

    /**
     * Get a description of the validation rules in as text
     *
     * @param array $rules Validation rules
     * @return string
     */
    public static function getDescription($rules)
    {
        $text = '';
        if (isset($rules['imageSize'])) {
            $text .= self::getImageSizeDescription($rules['imageSize']);
            $text = !empty($text) ? $text . '<br>' : $text;
        }

        if (isset($rules['extensions'])) {
            $text .= self::getExtensionDescription($rules['extensions']);
            $text = isset($rules['maxSize']) ? $text . '<br>' : $text;
        }

        if (isset($rules['maxSize'])) {
            $text .= self::getMaxSizeDescription($rules['maxSize']);
        }

        return $text;
    }
}
