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
 * This is the model class for table "file"
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
                if (!file_exists($this->path)) {
                    return false;
                }
                if (!Yii::$app instanceof \yii\console\Application) {
                    $this->user_id = Yii::$app->user->isGuest ? 0 : Yii::$app->user->id; // @codeCoverageIgnore
                    $this->ip = ip2long(Yii::$app->request->getUserIP()); // @codeCoverageIgnore
                } // @codeCoverageIgnore

                $pathInfo = pathinfo($this->path);

                $this->size = filesize($this->path);
                $this->mime = FileHelper::getMimeType($this->path);
                $this->title = $pathInfo['filename'];
                $this->extension = current(FileHelper::getExtensionsByMimeType($this->mime));
                $this->name = $this->generateName();
            }

            return true;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     * Set a storage
     *
     * @param Storage $storage
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
     * Create a file
     *
     * @param string $path
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $temporary
     * @param bool $protected
     * @return File|bool
     */
    public static function create($path, $ownerId, $ownerType, $temporary, $protected)
    {
        $file = new File();
        $file->path = $path;
        $file->tmp = $temporary;
        $file->owner_id = $ownerId;
        $file->owner_type = $ownerType;
        $file->protected = $protected;

        if ($file->save()) {
            return $file;
        }

        return false;
    }

    /**
     * Find all by owner
     *
     * @param int $ownerId
     * @param int $ownerType
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
     * @param int $ownerId
     * @param int $ownerType
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
     * @param Storage $storage
     * @param int $ownerId
     * @param int $ownerType
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
     *
     * @return bool
     */
    public function beforeDelete()
    {
        $this->getStorage()->delete();
        return true;
    }
}
