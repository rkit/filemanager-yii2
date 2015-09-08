<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "file".
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
 * @property integer $position
 * @property integer $status
 */
class File extends \yii\db\ActiveRecord
{
    const STATUS_UNPROTECTED = 0;
    const STATUS_PROTECTED = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
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
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if (!Yii::$app instanceof \yii\console\Application) {
                    $this->user_id = Yii::$app->user->isGuest ? 0 : Yii::$app->user->id;
                    $this->ip = ip2long(Yii::$app->request->getUserIP());
                }

                // To remove unused files
                if ($this->owner_id === null) {
                    $this->owner_id = -1;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get all statuses.
     *
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_UNPROTECTED => Yii::t('filemanager-yii2', 'Unprotected, access from the web'),
            self::STATUS_PROTECTED  => Yii::t('filemanager-yii2', 'Protected'),
        ];
    }

    /**
     * Get statuse name
     *
     * @return string
     */
    public function getStatusName()
    {
        $statuses = $this->getStatuses();
        return isset($statuses[$this->status]) ? $statuses[$this->status] : '';
    }

    /**
     * Is it protected?
     *
     * @param bool
     */
    public function isProtected()
    {
        return $this->status == self::STATUS_PROTECTED;
    }

    /**
     * Is it unprotected?
     *
     * @param bool
     */
    public function isUnprotected()
    {
        return $this->status == self::STATUS_UNPROTECTED;
    }


    public function getTimestampOfFile()
    {
        if ($this->isNewRecord || is_object($this->date_create)) {
            return date('Ym');
        } else {
            return date_format(date_create($this->date_create), 'Ym');
        }
    }

    public function getUploadDir()
    {
        $uploadDir = $this->isProtected() ? 'uploadDirProtected' : 'uploadDirUnprotected';
        return Yii::getAlias(Yii::$app->fileManager->$uploadDir);
    }

    /**
     * Path to temporary directory of file.
     *
     * @param bool $full
     * @return string
     */
    public function dirTmp($full = false)
    {
        return
            ($full ? $this->getUploadDir() : '') . '/' .
            Yii::$app->fileManager->publicPath . '/' .
            'tmp/' .
            $this->owner_type . '/' .
            $this->getTimestampOfFile();
    }

    /**
     * Path to directory of file.
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
                $this->owner_type . '/' .
                $this->getTimestampOfFile() . '/' .
                $this->owner_id . '/' .
                $this->id;
        }
    }

    /**
     * Path to file.
     *
     * @param bool $full
     * @return string
     */
    public function pathTmp($full = false)
    {
        return $this->dirTmp($full) . '/'. $this->name;
    }

    /**
     * Path to file.
     *
     * @param bool $full
     * @return string
     */
    public function path($full = false)
    {
        return $this->dir($full) . '/'. $this->name;
    }

    /**
     * Generate a name.
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
     * Generate a thumb-name.
     *
     * @param string $file
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function generateThumbName($file, $width, $height)
    {
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        return str_replace($fileName, $width . 'x' . $height . '_' . $fileName, $file);
    }

    /**
     * Create file from uploader (UploadedFile).
     *
     * @param UploadedFile $data
     * @param int $ownerType
     * @param bool $saveAfterUpload Save the file immediately after upload.
     * @param int $status Status a file. Unprotected or Protected.
     * @return File|bool
     */
    public static function createFromUploader(
        $data,
        $ownerId = null,
        $ownerType = -1,
        $saveAfterUpload = false,
        $status = self::STATUS_UNPROTECTED
    ) {
        $fileInfo = pathinfo($data->name);
        $file = new self([
            'tmp' => true,
            'owner_id' => $ownerId,
            'owner_type' => $ownerType,
            'size' => $data->size,
            'mime' => $data->type,
            'title' => $fileInfo['filename'],
            'name' => self::generateName($fileInfo['extension']),
            'status' => $status
        ]);

        if (!Yii::$app instanceof \yii\console\Application) {
            return $file->moveUploadedFile($data->tempName, $saveAfterUpload);
        } else {
            return $file->renameUploadedFile($data->tempName, $saveAfterUpload);
        }
    }

    /**
     * Create file from Url
     *
     * @param string $url
     * @param int $ownerType
     * @param bool $saveAfterUpload Save the file immediately after upload.
     * @param int $status Status a file. Unprotected or Protected.
     * @return File|bool
     */
    public static function createFromUrl(
        $url,
        $ownerId = null,
        $ownerType = -1,
        $saveAfterUpload = false,
        $status = self::STATUS_UNPROTECTED
    ) {
        $tmpFile = tempnam(sys_get_temp_dir(), 'file');
        if ($tmpFileContent = @file_get_contents($url)) {
            if (@file_put_contents($tmpFile, $tmpFileContent)) {
                $fileInfo = pathinfo($url);
                $file = new self([
                    'tmp' => true,
                    'owner_id' => $ownerId,
                    'owner_type' => $ownerType,
                    'size' => filesize($tmpFile),
                    'mime' => FileHelper::getMimeType($tmpFile),
                    'title' => $fileInfo['filename'],
                    'name' => self::generateName($fileInfo['extension']),
                    'status' => $status
                ]);

                return $file->renameUploadedFile($tmpFile, $saveAfterUpload);
            }
        }

        return false;
    }

    private function moveUploadedFile($tempFile, $saveAfterUpload)
    {
        if (FileHelper::createDirectory($this->dir(true))) {
            if (move_uploaded_file($tempFile, $this->path(true))) {
                if ($saveAfterUpload) {
                    $this->tmp = false;
                    if ($this->save() && $this->saveFile()) {
                        return $this;
                    }
                } else {
                    if ($this->save()) {
                        return $this;
                    }
                }
            }
        }

        return false;
    }

    private function renameUploadedFile($tempFile, $saveAfterUpload)
    {
        if (FileHelper::createDirectory($this->dir(true))) {
            if (rename($tempFile, $this->path(true))) {
                if ($saveAfterUpload) {
                    $this->tmp = false;
                    if ($this->save() && $this->saveFile()) {
                        return $this;
                    }
                } else {
                    if ($this->save()) {
                        return $this;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Save file.
     *
     * @return bool
     */
    public function saveFile()
    {
        if (file_exists($this->pathTmp(true)) && FileHelper::createDirectory($this->dir(true))) {
            if (rename($this->pathTmp(true), $this->path(true))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @return bool
     */
    public function isOwner($ownerId, $ownerType)
    {
        $ownerType = $this->owner_type === $ownerType;
        $ownerId = $this->owner_id === $ownerId;
        $user = $this->user_id === Yii::$app->user->id || $this->user_id === 0;

        return
            (!$this->tmp && $ownerType && $ownerId) ||
            ($this->tmp && $ownerType && $user);
    }

    /**
     * Get by owner.
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
     * Delete by owner.
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
     * Deleting a file from the db and from the file system.
     *
     * @return bool
     */
    public function beforeDelete()
    {
        FileHelper::removeDirectory($this->dir(true));
        return true;
    }
}
