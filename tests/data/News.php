<?php

namespace rkit\filemanager\tests\data;

use Yii;

class News extends \yii\db\ActiveRecord
{
    /**
     * @var array
     */
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
            [['title', 'preview', 'photo_id', 'gallery'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => 'rkit\filemanager\behaviors\FileBehavior',
                'attributes' => [
                    'photo_id' => [
                        'ownerType' => 'news.photo_id',
                        'saveFileId' => true, // save 'file.id' in current model
                        'rules' => [
                            'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                            'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                            'extensions' => ['jpg', 'jpeg', 'png'],
                            'maxSize' => 1024 * 1024 * 1, // 1 MB
                            'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
                        ]
                    ],
                    'preview' => [
                        'ownerType' => 'news.preview',
                        'savePath' => true, // save 'path' in current model
                        'rules' => [
                            'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                            'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                            'extensions' => ['jpg', 'jpeg', 'png'],
                            'maxSize' => 1024 * 1024 * 1, // 1 MB
                            'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
                        ]
                    ],
                    'gallery' => [
                        'ownerType' => 'news.gallery',
                        'rules' => [
                            'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                            'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                            'extensions' => ['jpg', 'jpeg', 'png'],
                            'maxSize' => 1024 * 1024 * 1, // 1 MB
                            'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
                        ]
                    ]
                ]
            ]
        ];
    }
}
