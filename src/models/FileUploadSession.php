<?php
/**
 * Alphatech, <http://www.alphatech.com.ua>
 *
 * Copyright (C) 2018-present Sergii Webkadabra <devkadabra@gmail.com>
 */

namespace rkit\filemanager\models;

/**
 * Class FileUploadSession
 * @package rkit\filemanager\models
 *
 * @property int $id
 * @property int $file_id
 * @property int $created_user_id
 * @property string $created_on
 * @property string $target_model_class
 * @property string $target_model_id
 * @property string $target_model_attribute
 */
class FileUploadSession  extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file_upload_session}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_user_id',
                'updatedByAttribute' => false,
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_on',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
}