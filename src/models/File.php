<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "file"
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $owner_id
 * @property integer $owner_type
 * @property string $title
 * @property string $name
 * @property integer $size
 * @property string $mime
 * @property string $date_create
 * @property string $date_update
 * @property integer $ip
 * @property integer $tmp
 * @property integer $position
 * @property integer $protected
 */
class File extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('filemanager-yii2', 'ID'),
            'user_id' => Yii::t('filemanager-yii2', 'User'),
            'owner_id' => Yii::t('filemanager-yii2', 'Owner'),
            'owner_type' => Yii::t('filemanager-yii2', 'Owner type'),
            'title' => Yii::t('filemanager-yii2', 'Title'),
            'name' => Yii::t('filemanager-yii2', 'Name'),
            'size' => Yii::t('filemanager-yii2', 'Size'),
            'mime' => Yii::t('filemanager-yii2', 'Mime'),
            'date_create' => Yii::t('filemanager-yii2', 'Date create'),
            'date_update' => Yii::t('filemanager-yii2', 'Date update'),
            'ip' => Yii::t('filemanager-yii2', 'IP'),
            'position' => Yii::t('filemanager-yii2', 'Position'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'date_create',
                'updatedAtAttribute' => 'date_update',
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if (!Yii::$app instanceof \yii\console\Application) {
                    $this->user_id = Yii::$app->user->isGuest ? 0 : Yii::$app->user->id; // @codeCoverageIgnore
                    $this->ip = ip2long(Yii::$app->request->getUserIP()); // @codeCoverageIgnore
                } // @codeCoverageIgnore
            }

            return true;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     * Is it protected?
     *
     * @return bool
     */
    public function isProtected()
    {
        return (bool)$this->protected;
    }

    /**
     * Is it unprotected?
     *
     * @return bool
     */
    public function isUnprotected()
    {
        return (bool)$this->protected === false;
    }

    /**
     * Is it tmp a file?
     *
     * @return bool
     */
    public function isTmp()
    {
        return (bool)$this->tmp;
    }

    /**
     * Get date create of file in format `Ym`
     *
     * @return string
     */
    public function getDateOfFile()
    {
        if ($this->isNewRecord || is_object($this->date_create)) {
            return date('Ym');
        } else {
            return date_format(date_create($this->date_create), 'Ym');
        }
    }

    /**
     * Get upload dir
     *
     * @return string
     */
    public function getUploadDir()
    {
        if ($this->isProtected()) {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirProtected);
        } else {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected);
        }
    }

    /**
     * Path to temporary directory of file
     *
     * @param bool $full
     * @return string
     */
    public function dirTmp($full = false)
    {
        return
            ($full ? $this->getUploadDir() : '') . '/' .
            Yii::$app->fileManager->publicPath . '/tmp/' .
            $this->getDateOfFile() . '/' .
            $this->owner_type . '/' .
            $this->id;
    }

    /**
     * Path to directory of file
     *
     * @param bool $full
     * @return string
     */
    public function dir($full = false)
    {
        if ($this->tmp) {
            return $this->dirTmp($full);
        } else {
            return
                ($full ? $this->getUploadDir() : '') . '/' .
                Yii::$app->fileManager->publicPath . '/' .
                $this->getDateOfFile() . '/' .
                $this->owner_type . '/' .
                $this->owner_id . '/' .
                $this->id;
        }
    }

    /**
     * Path to file
     *
     * @param bool $full
     * @return string
     */
    public function pathTmp($full = false)
    {
        return $this->dirTmp($full) . '/'. $this->name;
    }

    /**
     * Path to file
     *
     * @param bool $full
     * @return string
     */
    public function path($full = false)
    {
        return $this->dir($full) . '/'. $this->name;
    }

    /**
     * Generate a name
     *
     * @param string $extension
     * @return string
     */
    public static function generateName($extension = null)
    {
        $name = date('YmdHis') . substr(md5(microtime() . uniqid()), 0, 10);
        return $extension ? $name . '.' . $extension : $name;
    }

    /**
     * Save file in tmp directory (if `saveAfterUpload` is true then save in the final directory)
     *
     * @param string $tempFile
     * @param bool $saveAfterUpload
     * @param bool $uploaded File has been uploaded or manually created
     * @return \rkit\filemanager\models\File|bool
     */
    public function saveToTmp($tempFile, $saveAfterUpload = false, $uploaded = true)
    {
        if ($this->save()) {
            if (FileHelper::createDirectory($this->dirTmp(true))) {
                $processed = $this->moveUploadedFile($tempFile, $uploaded);
                if ($processed && $saveAfterUpload) {
                    $this->tmp = false;
                    $this->updateAttributes(['tmp' => $this->tmp]);
                    if ($this->saveFile()) {
                        return $this;
                    }
                } elseif ($processed) {
                    return $this;
                }
            } // @codeCoverageIgnore
        } // @codeCoverageIgnore

        return false; // @codeCoverageIgnore
    }

    /**
     * Save file.
     *
     * @return bool
     */
    public function saveFile()
    {
        if (file_exists($this->pathTmp(true))) {
            FileHelper::copyDirectory($this->dirTmp(true), $this->dir(true));
            FileHelper::removeDirectory($this->dirTmp(true));
            return true;
        } // @codeCoverageIgnore

        return false;
    }

    /**
     * Save file
     *
     * @param string $tempFile
     * @param bool $uploaded File has been uploaded or manually created
     * @return bool
     */
    private function moveUploadedFile($tempFile, $uploaded = true)
    {
        if (!$uploaded || Yii::$app instanceof \yii\console\Application) {
            return rename($tempFile, $this->pathTmp(true));
        } else {
            return move_uploaded_file($tempFile, $this->pathTmp(true)); // @codeCoverageIgnore
        }
    }

    /**
     * Check owner
     *
     * @param int $ownerId
     * @param int $ownerType
     * @return bool
     */
    public function isOwner($ownerId, $ownerType)
    {
        $ownerType = $this->owner_type === $ownerType;
        $ownerId = $this->owner_id === $ownerId;
        $user = $this->user_id === Yii::$app->user->id || $this->user_id === 0;

        return (!$this->tmp && $ownerType && $ownerId) || ($this->tmp && $ownerType && $user);
    }

    /**
     * Get by owner
     *
     * @param int $ownerId
     * @param int $ownerType
     * @return array
     */
    public static function getByOwner($ownerId, $ownerType)
    {
        return static::find()
            ->where(['owner_id' => $ownerId, 'owner_type' => $ownerType])
            ->orderBy('position ASC')
            ->all();
    }

    /**
     * Delete by owner
     *
     * @param int $ownerId
     * @param int $ownerType
     */
    public static function deleteByOwner($ownerId, $ownerType)
    {
        $files = self::getByOwner($ownerId, $ownerType);

        foreach ($files as $file) {
            $file->delete();
        }
    }

    /**
     * Deleting a file from the db and from the file system
     *
     * @return bool
     */
    public function beforeDelete()
    {
        FileHelper::removeDirectory($this->dir(true));
        return true;
    }
}
