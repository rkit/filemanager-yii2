<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use rkit\filemanager\models\File;

class FileBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT  => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE  => 'afterSave',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    public function beforeSave($insert)
    {
        foreach ($this->attributes as $attribute => $data) {
            $oldValue = $this->owner->isNewRecord ? null : $this->owner->getOldAttribute($attribute);
            $isAttributeChanged = $oldValue === null ? true : $this->owner->isAttributeChanged($attribute);

            $this->attributes[$attribute]['isAttributeChanged'] = $isAttributeChanged;
            $this->attributes[$attribute]['oldValue'] = $oldValue;
        }
    }

    public function afterSave()
    {
        foreach ($this->attributes as $attribute => $data) {
            $fileId = $this->owner->{$attribute};

            if ($data['isAttributeChanged'] === false || $fileId === null) {
                continue;
            }

            $ownerType = Yii::$app->fileManager->getOwnerType($data['ownerType']);
            $file = File::bind($this->owner->primaryKey, $ownerType, $fileId);

            if (isset($data['savePath']) && $data['savePath'] === true) {
                $this->owner->updateAttributes([$attribute => $this->getFilePath($file, $data['oldValue'])]);
            }

            if (isset($data['saveFileId']) && $data['saveFileId'] === true) {
                $this->owner->updateAttributes([$attribute => $this->getFileId($file, $data['oldValue'])]);
            }
        }
    }

    public function beforeDelete()
    {
        foreach ($this->attributes as $attribute => $data) {
            $ownerType = Yii::$app->fileManager->getOwnerType($data['ownerType']);
            File::deleteByOwner($this->owner->primaryKey, $ownerType);
        }
    }

    /**
     * Get file path.
     *
     * @param mixed $file
     * @param mixed $oldValue
     * @return string
     */
    private function getFilePath($file, $oldValue)
    {
        if (is_object($file)) {
            return $file->path();
        } elseif ($file === false && $oldValue !== null) {
            return $oldValue;
        } else {
            return '';
        }
    }

    /**
     * Get file id.
     *
     * @param mixed $file
     * @param mixed $oldValue
     * @return int
     */
    private function getFileId($file, $oldValue)
    {
        if (is_object($file)) {
            return $file->id;
        } elseif ($file === false && $oldValue !== null) {
            return $oldValue;
        } else {
            return 0;
        }
    }

    /**
     * Get ownerType.
     *
     * @param string $attribute
     * @return int
     */
    public function getFileOwnerType($attribute)
    {
        return Yii::$app->fileManager->getOwnerType($this->attributes[$attribute]['ownerType']);
    }

    /**
     * Get files.
     *
     * @param string $attribute
     * @return array
     */
    public function getFiles($attribute)
    {
        return File::getByOwner($this->owner->primaryKey, $this->getFileOwnerType($attribute));
    }

    /**
     * Get rules.
     *
     * @param string $attribute
     * @return array
     */
    public function getFileRules($attribute)
    {
        return $this->attributes[$attribute]['rules'];
    }

    /**
     * Get resize rule.
     *
     * @param string $attribute
     * @return array
     */
    public function getFileResizeRules($attribute)
    {
        return ArrayHelper::getValue($this->attributes[$attribute], 'resize', []);
    }

    /**
     * Get rules description.
     *
     * @param string $attribute
     * @return string
     */
    public function getFileRulesDescription($attribute)
    {
        $text = '';

        $rules = $this->attributes[$attribute]['rules'];

        if (isset($rules['imageSize'])) {
            $text .= $this->prepareImageSizeDescription($rules['imageSize']);
            $text = !empty($text) ? $text . '<br>' : $text;
        }

        if (isset($rules['extensions'])) {
            $text .= $this->prepareExtensionDescription($rules['extensions']);
            $text = isset($rules['maxSize']) ? $text . '<br>' : $text;
        }

        if (isset($rules['maxSize'])) {
            $text .= $this->prepareMaxSizeDescription($rules['maxSize']);
        }

        return $text;
    }

    private function prepareMaxSizeDescription($rules)
    {
        $maxSize = Yii::$app->formatter->asShortSize($rules);
        return Yii::t('app', 'Max. file size') . ': ' . $maxSize . ' ';
    }

    private function prepareExtensionDescription($rules)
    {
        $extensions = strtoupper(implode(', ', $rules));
        return Yii::t('app', 'File types') . ': ' . $extensions . ' ';
    }

    private function prepareImageSizeDescription($rules)
    {
        $maxWidth  = ArrayHelper::getValue($rules, 'maxWidth');
        $minWidth  = ArrayHelper::getValue($rules, 'minWidth');
        $maxHeight = ArrayHelper::getValue($rules, 'maxHeight');
        $minHeight = ArrayHelper::getValue($rules, 'minHeight');

        $text = '';
        if (count($rules) == 4 && ($maxWidth == $minWidth && $maxHeight == $minHeight)) {
            $text .= Yii::t('app', 'Image size') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif (count($rules) == 4) {
            $text .= Yii::t('app', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= Yii::t('app', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
        } elseif ((count($rules) == 2 || count($rules) == 3) && $minWidth && $minHeight) {
            $text .= Yii::t('app', 'Min. size of image') . ': ' . $minWidth . 'x' . $minHeight . 'px;';
            $text .= $this->prepareImageFullSizeDescription($rules, ['minWidth', 'minHeight']);
        } elseif ((count($rules) == 2 || count($rules) == 3) && $maxWidth && $maxHeight) {
            $text .= Yii::t('app', 'Max. size of image') . ': ' . $maxWidth . 'x' . $maxHeight . 'px;';
            $text .= $this->prepareImageFullSizeDescription($rules, ['maxWidth', 'maxHeight']);
        } else {
            $text .= $this->prepareImageFullSizeDescription($rules);
        }

        $text = mb_substr($text, 0, -1);
        $text = str_replace(';', '<br>', $text);

        return $text;
    }

    private function prepareImageFullSizeDescription($rules, $exclude = [])
    {
        foreach ($exclude as $item) {
            unset($rules[$item]);
        }

        $text = '';
        foreach ($rules as $rule => $value) {
            switch ($rule) {
                case 'minWidth':
                    $text .= Yii::t('app', 'Min. width') . ' ' . $value . 'px;';
                    break;
                case 'minHeight':
                    $text .= Yii::t('app', 'Min. height') . ' ' . $value . 'px;';
                    break;
                case 'maxWidth':
                    $text .= Yii::t('app', 'Max. width') . ' ' . $value . 'px;';
                    break;
                case 'maxHeight':
                    $text .= Yii::t('app', 'Max. height') . ' ' . $value . 'px;';
                    break;
            }
        }

        return $text;
    }
}
