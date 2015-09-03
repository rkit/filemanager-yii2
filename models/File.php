<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager\models;

use Yii;
use yii\imagine;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;

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
    public function rules()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User'),
            'owner_id' => Yii::t('app', 'Owner'),
            'owner_type' => Yii::t('app', 'Owner type'),
            'title' => Yii::t('app', 'Title'),
            'name' => Yii::t('app', 'Name'),
            'size' => Yii::t('app', 'Size'),
            'mime' => Yii::t('app', 'Mime'),
            'date_create' => Yii::t('app', 'Date create'),
            'date_update' => Yii::t('app', 'Date update'),
            'ip' => Yii::t('app', 'IP'),
            'position' => Yii::t('app', 'Position'),
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
            self::STATUS_UNPROTECTED => Yii::t('app', 'Unprotected, access from the web'),
            self::STATUS_PROTECTED  => Yii::t('app', 'Protected'),
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


    private function getTimestampOfFile()
    {
        if ($this->isNewRecord || is_object($this->date_create)) {
            return date('Ym');
        } else {
            return date_format(date_create($this->date_create), 'Ym');
        }
    }

    private static function getUploadDir($status)
    {
        if ($status === self::STATUS_PROTECTED) {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirProtected);
        } else {
            return Yii::getAlias(Yii::$app->fileManager->uploadDirUnprotected);
        }
    }

    /**
     * Path to temporary directory of file.
     *
     * @param bool $full
     * @return string
     */
    public function dirTmp($full = false)
    {
        $uploadDir = $full ? self::getUploadDir($this->status) : '';
        return
            $uploadDir . '/' .
            Yii::$app->fileManager->publicPath . '/tmp/' .
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
            $uploadDir = $full ? self::getUploadDir($this->status) : '';
            return
                $uploadDir . '/' .
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
        $ownerType,
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

        return $file->moveUploadedFile($data->tempName, $saveAfterUpload);
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
        $ownerType,
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
    private function saveFile()
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
     * @param File $file
     * @param int $ownerId
     * @param int $ownerType
     * @return bool
     */
    private static function checkOwner($file, $ownerId, $ownerType)
    {
        $ownerType = $file->owner_type === $ownerType;
        $ownerId = $file->owner_id === $ownerId;
        $user = $file->user_id === Yii::$app->user->id || $file->user_id === 0;

        return
            (!$file->tmp && $ownerType && $ownerId) ||
            ($file->tmp && $ownerType && $user);
    }

    /**
     * Binding files with owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param array|int $fileId
     * @return File|bool|array
     */
    public static function bind($ownerId, $ownerType, $fileId)
    {
        if ($fileId === [] || $fileId === '') {
            self::deleteByOwner($ownerId, $ownerType);
            return true;
        }

        return is_array($fileId)
            ? self::bindMultiple($ownerId, $ownerType, $fileId)
            : self::bindSingle($ownerId, $ownerType, $fileId);
    }

    /**
     * Binding file with owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param int $fileId
     * @return File|bool
     */
    private static function bindSingle($ownerId, $ownerType, $fileId)
    {
        $file = $fileId ? static::findOne($fileId) : false;

        // check owner
        if (!$file || !self::checkOwner($file, $ownerId, $ownerType)) {
            return false;
        }

        // save a file
        if ($file->tmp) {
            $file->owner_id = $ownerId;
            $file->tmp = false;
            if ($file->saveFile()) {
                $file->updateAttributes(['tmp' => $file->tmp, 'owner_id' => $file->owner_id]);
            }
        } else {
            return false;
        }

        // delete unnecessary files
        $currentFiles = self::getByOwner($ownerId, $ownerType);
        foreach ($currentFiles as $currFile) {
            if ($currFile->id !== $file->id) {
                $currFile->delete();
            }
        }

        return $file;
    }

    private static function bindMultiplePrepare($files)
    {
        $files = array_filter($files);
        $files = array_combine(array_map(function ($a) {
            return substr($a, 2);
        }, array_keys($files)), $files);

        return $files;
    }

    private static function bindMultipleFile($file, $ownerId, $files)
    {
        if ($file->tmp) {
            $file->owner_id = $ownerId;
            $file->tmp = false;
            if (!$file->saveFile()) {
                return false;
            }
        }

        $file->updateAttributes([
            'tmp'      => $file->tmp,
            'owner_id' => $file->owner_id,
            'title'    => @$files[$file->id],
            'position' => @array_search($file->id, array_keys($files)) + 1
        ]);

        return true;
    }

    /**
     * Binding files with owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param array $files
     * @return array|bool
     */
    private static function bindMultiple($ownerId, $ownerType, $files)
    {
        $files = self::bindMultiplePrepare($files);
        $newFiles = ArrayHelper::index(static::findAll(array_keys($files)), 'id');
        $currentFiles = ArrayHelper::index(self::getByOwner($ownerId, $ownerType), 'id');

        if (count($newFiles)) {
            foreach ($newFiles as $file) {
                // check owner
                if (!self::checkOwner($file, $ownerId, $ownerType)) {
                    unset($newFiles[$file->id]);
                    continue;
                }
                // save a file
                self::bindMultipleFile($file, $ownerId, $files, $newFiles);
            }

            // delete unnecessary files
            foreach ($currentFiles as $currFile) {
                if (!array_key_exists($currFile->id, $newFiles)) {
                    $currFile->delete();
                }
            }

        } else {
            // if empty array — delete current files
            foreach ($currentFiles as $currFile) {
                $currFile->delete();
            }
        }

        return $newFiles;
    }

    /**
     * Resize.
     *
     * @param string $file
     * @param int $width
     * @param int $height
     * @param bool $ratio
     * @param bool $replace
     * @param int $status Status a file. Unprotected or Protected.
     * @return string
     */
    public static function resize(
        $file,
        $width,
        $height,
        $ratio = false,
        $replace = false,
        $status = self::STATUS_UNPROTECTED
    ) {
        $fullPathToDir = self::getUploadDir($status);

        if (!file_exists($fullPathToDir . $file)) {
            return $file;
        }

        if ($replace) {
            $thumb = $file;
        } else {
            $thumb = self::generateThumbName($file, $width, $height);
            if (file_exists($fullPathToDir . $thumb)) {
                return $thumb;
            }
        }

        $imagine = imagine\Image::getImagine();
        $image = $imagine->open($fullPathToDir . $file);
        $image = self::resizeMagic($image, $width, $height, $ratio);
        $image->save($fullPathToDir . $thumb, ['jpeg_quality' => 100, 'png_compression_level' => 9]);

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
    private static function resizeMagic($image, $width, $height, $ratio)
    {
        if ($width < 1 || $height < 1) {
            if ($height < 1) {
                $image = $image->resize($image->getSize()->widen($width));
            } else {
                $image = $image->resize($image->getSize()->heighten($height));
            }

        } else {
            $size = new Box($width, $height);

            if ($ratio) {
                $mode = ImageInterface::THUMBNAIL_INSET;
            } else {
                $mode = ImageInterface::THUMBNAIL_OUTBOUND;
            }

            $image = $image->thumbnail($size, $mode);
        }

        return $image;
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
