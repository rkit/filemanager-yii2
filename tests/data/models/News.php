<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests\data\models;

use tests\data\models\File;

class News extends \yii\db\ActiveRecord
{
    public $gallery;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'news';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'image', 'gallery'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImageFile()
    {
        return $this
            ->hasOne(File::class, ['id' => 'file_id'])
            ->viaTable('{{%news_files}}', ['news_id' => 'id'], function ($query) {
                $query->where(['type' => 1]);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGalleryFiles()
    {
        return $this
            ->hasMany(File::class, ['id' => 'file_id'])
            ->viaTable('{{%news_files}}', ['news_id' => 'id'], function ($query) {
                $query->where(['type' => 2]);
            })
            ->innerJoin('{{%news_files}}', '`file_id` = `file`.`id`')
            ->orderBy(['position' => SORT_ASC]);
    }
}
