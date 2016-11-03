<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests\data\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * ActiveRecord for table "file"
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $name
 * @property string $date_create
 * @property string $date_update
 * @property integer $ip
 */
class File extends \yii\db\ActiveRecord
{
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
     * @internal
     */
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
     * Generate a new name
     *
     * @param string $extension The file extension
     * @return string
     */
    public function generateName($extension)
    {
        $name = date('YmdHis') . substr(md5(microtime() . uniqid()), 0, 10);
        $this->name = $name . '.' . $extension;
    }
}
