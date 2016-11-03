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

    public function getFiles($callable = null)
    {
        return $this
            ->hasMany(File::className(), ['id' => 'file_id'])
            ->viaTable('news_files', ['news_id' => 'id'], $callable);
    }
}
