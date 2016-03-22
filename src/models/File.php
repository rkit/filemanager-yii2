<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace rkit\filemanager\models;

use Yii;
use yii\base\InvalidParamException;
use yii\behaviors\TimestampBehavior;
use yii\helpers\FileHelper;
use rkit\filemanager\Storage;

/**
 * ActiveRecord for table "file"
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $owner_id
 * @property integer $owner_type
 * @property string $title
 * @property string $name
 * @property integer $size
 * @property string $extension
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
     * @var string
     */
    public $path;
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     * @internal
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     * @internal
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
            'extension' => Yii::t('filemanager-yii2', 'Extension'),
            'mime' => Yii::t('filemanager-yii2', 'Mime'),
            'date_create' => Yii::t('filemanager-yii2', 'Date create'),
            'date_update' => Yii::t('filemanager-yii2', 'Date update'),
            'ip' => Yii::t('filemanager-yii2', 'IP'),
            'position' => Yii::t('filemanager-yii2', 'Position'),
        ];
    }

    /**
     * @inheritdoc
     * @internal
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
     * @internal
     */
    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @internal
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if (!file_exists($this->path)) {
                    return false;
                }

                $this->fillUserInfo();
                $this->fillMetaInfo();

                if ($this->owner_id === null) {
                    $this->owner_id = 0;
                }
            }

            return true;
        }

        return false; // @codeCoverageIgnore
    }

    private function fillUserInfo()
    {
        if (!Yii::$app instanceof \yii\console\Application) {
            $this->user_id = Yii::$app->user->isGuest ? 0 : Yii::$app->user->id; // @codeCoverageIgnore
            $this->ip = ip2long(Yii::$app->request->getUserIP()); // @codeCoverageIgnore
        } // @codeCoverageIgnore
    }

    private function fillMetaInfo()
    {
        $pathInfo = pathinfo($this->path);

        if ($this->title === null) {
            $this->title = $pathInfo['filename'];
        }

        $this->size = filesize($this->path);
        $this->mime = FileHelper::getMimeType($this->path);
        $this->extension = $this->getExtensionByMimeType($this->mime);
        $this->name = $this->generateName();
    }

    private function getExtensionByMimeType($mimeType)
    {
        $extensions = FileHelper::getExtensionsByMimeType($mimeType);
        $pathInfo = pathinfo($this->path);
        $titleInfo = pathinfo($this->title);

        if (isset($pathInfo['extension'])) {
            $extension = $pathInfo['extension'];
        } elseif (isset($titleInfo['extension'])) {
            $extension = $titleInfo['extension'];
        } else {
            $extension = explode('/', $mimeType);
            $extension = end($extension);
        }

        if (array_search($extension, $extensions) !== false) {
            return $extension;
        }

        return current($extensions);
    }

    /**
     * Set a storage
     *
     * @param Storage $storage The Strorage for the file
     * @return string
     */
    public function setStorage(Storage $storage)
    {
        $this->storage = $storage;
        $this->storage->setFile($this);

        return $this;
    }

    /**
     * Get a storage
     *
     * @return string
     * @throws InvalidParamException
     */
    public function getStorage()
    {
        if ($this->storage === null) {
            throw new InvalidParamException('The storage is not initialized');
        }

        return $this->storage;
    }

    /**
     * Generate a name
     *
     * @return string
     */
    public function generateName()
    {
        $name = date('YmdHis') . substr(md5(microtime() . uniqid()), 0, 10);
        return $name . '.' . $this->extension;
    }

    /**
     * Checks whether the file is protected
     *
     * @return bool
     */
    public function isProtected()
    {
        return (bool)$this->protected;
    }

    /**
     * Checks whether the file is unprotected
     *
     * @return bool
     */
    public function isUnprotected()
    {
        return (bool)$this->protected === false;
    }

    /**
     * Checks whether the file is temp
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
     * Checks whether the owner of the file
     *
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
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
     * Find all by owner
     *
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     * @return array
     */
    public static function findAllByOwner($ownerId, $ownerType)
    {
        return static::find()
            ->where(['owner_id' => $ownerId, 'owner_type' => $ownerType])
            ->orderBy('position ASC')
            ->all();
    }

    /**
     * Find one by owner
     *
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     * @return File|null
     */
    public static function findOneByOwner($ownerId, $ownerType)
    {
        return static::find()
            ->where(['owner_id' => $ownerId, 'owner_type' => $ownerType])
            ->one();
    }

    /**
     * Delete by owner
     *
     * @param Storage $storage The storage of the file
     * @param int $ownerId The id of the owner
     * @param int $ownerType The type of the owner
     */
    public function deleteByOwner($storage, $ownerId, $ownerType)
    {
        $files = self::findAllByOwner($ownerId, $ownerType);

        foreach ($files as $file) {
            $file->setStorage($storage);
            $file->delete();
        }
    }

    /**
     * Deleting a file from the db and from the file system
     * @internal
     *
     * @return bool
     */
    public function beforeDelete()
    {
        $this->getStorage()->delete();
        return true;
    }
}
