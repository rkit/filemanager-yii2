<?php

namespace rkit\filemanager\helpers;

use Yii;
use yii\helpers\ArrayHelper;

class FileRules
{
    /**
     * Prepare description for max size of file
     *
     * @param int $rules
     * @return string
     */
    public static function prepareMaxSizeDescription($rules)
    {
        $maxSize = Yii::$app->formatter->asShortSize($rules);
        return Yii::t('filemanager-yii2', 'Max. file size') . ': ' . $maxSize . ' ';
    }

    /**
     * Prepare description for extensions of file
     *
     * @param array $rules
     * @return string
     */
    public static function prepareExtensionDescription($rules)
    {
        $extensions = strtoupper(implode(', ', $rules));
        return Yii::t('filemanager-yii2', 'File types') . ': ' . $extensions . ' ';
    }

    /**
     * Prepare description for size of image
     *
     * @param array $rules
     * @return string
     */
    public static function prepareImageSizeDescription($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        $text = '';
        if (self::imageWithStrictSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Image size') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif (self::imageWithMinAndMaxSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= Yii::t('filemanager-yii2', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif (self::imageWithMinSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= self::prepareImageFullSizeDescription($rules, ['minWidth', 'minHeight']);
        } elseif (self::imageWithMaxSize($rules)) {
            $text .= Yii::t('filemanager-yii2', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
            $text .= self::prepareImageFullSizeDescription($rules, ['maxWidth', 'maxHeight']);
        } else {
            $text .= self::prepareImageFullSizeDescription($rules);
        }

        $text = mb_substr($text, 0, -1);
        $text = str_replace(';', '<br>', $text);

        return $text;
    }

    /**
     * Prepare description for strict size of image
     *
     * @param array $rules
     * @return string
     */
    public static function imageWithStrictSize($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        return count($rules) == 4 && ($maxWidth == $minWidth && $maxHeight == $minHeight);
    }

    /**
     * Prepare description for min-max size of image
     *
     * @param array $rules
     * @return string
     */
    public static function imageWithMinAndMaxSize($rules)
    {
        return count($rules) == 4;
    }

    /**
     * Prepare description for min size of image
     *
     * @param array $rules
     * @return string
     */
    public static function imageWithMinSize($rules)
    {
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        return (count($rules) == 2 || count($rules) == 3) && $minWidth && $minHeight;
    }

    /**
     * Prepare description for max size of image
     *
     * @param array $rules
     * @return string
     */
    public static function imageWithMaxSize($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');

        return (count($rules) == 2 || count($rules) == 3) && $maxWidth && $maxHeight;
    }

    /**
     * Prepare description for full size of image
     *
     * @param array $rules
     * @return string
     */
    public static function prepareImageFullSizeDescription($rules, $exclude = [])
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
}
