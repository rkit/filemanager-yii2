<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests\data;

use Yii;
use Intervention\Image\ImageManagerStatic as Image;

class News extends \yii\db\ActiveRecord
{
    /**
     * @var array
     */
    public $image_gallery;
    /**
     * @var array
     */
    public $image_gallery_protected;
    /**
     * @var string
     */
    public $image_strict_size;
    /**
     * @var string
     */
    public $image_min_max_size;
    /**
     * @var string
     */
    public $image_min_size;
    /**
     * @var string
     */
    public $image_max_size;
    /**
     * @var string
     */
    public $image_only_maxwidth;
    /**
     * @var string
     */
    public $image_only_maxheight;
    /**
     * @var string
     */
    public $image_only_minwidth;
    /**
     * @var string
     */
    public $image_only_minheight;

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
            [['title', 'image_id', 'image_path', 'image_gallery'], 'safe'],
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
                    'image_id' => [
                        'saveFileId' => true,
                        'protected' => true,
                        'rules' => [
                            'imageSize' => ['minWidth' => 300, 'minHeight' => 300]
                        ],
                        'preset' => [
                            '200x200' => function ($realPath, $publicPath, $thumbPath) {
                                Image::make($realPath . $publicPath)
                                    ->fit(200, 200)
                                    ->save($realPath . $thumbPath, 100);
                            },
                            '220x220' => function ($realPath, $publicPath, $thumbPath) {
                                Image::make($realPath . $publicPath)
                                    ->fit(220, 220)
                                    ->save($realPath . $thumbPath, 100);
                            },
                        ],
                        'applyPresetAfterUpload' => '*'
                    ],
                    'image_path' => [
                        'saveFilePath' => true,
                        'rules' => [
                            'imageSize' => ['minWidth' => 300, 'minHeight' => 300],
                            'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg'],
                            'extensions' => ['jpg', 'jpeg', 'png'],
                            'maxSize' => 1024 * 1024 * 1, // 1 MB
                            'tooBig' => Yii::t('app', 'File size must not exceed') . ' 1Mb'
                        ],
                        'preset' => [
                            '200x200' => function ($realPath, $publicPath, $thumbPath) {
                                Image::make($realPath . $publicPath)
                                    ->fit(200, 200)
                                    ->save($realPath . $thumbPath, 100);
                            },
                            '220x220' => function ($realPath, $publicPath, $thumbPath) {
                                Image::make($realPath . $publicPath)
                                    ->fit(220, 220)
                                    ->save($realPath . $thumbPath, 100);
                            },
                            '400x400' => function ($realPath, $publicPath, $thumbPath) {
                                Image::make($realPath . $publicPath)
                                    ->fit(400, 400)
                                    ->save(null, 100);
                            },
                        ],
                        'applyPresetAfterUpload' => ['220x220']
                    ],
                    'image_gallery' => [
                        'preset' => [
                            '80x80' => function ($realPath, $publicPath, $thumbPath) {
                                Image::make($realPath . $publicPath)
                                    ->fit(80, 80)
                                    ->save($realPath . $thumbPath, 100);
                            },
                        ],
                    ],
                    'image_gallery_protected' => [
                        'protected' => true,
                    ],
                    'image_strict_size' => [
                        'rules' => [
                            'imageSize' => [
                                'maxWidth'  => 300,
                                'maxHeight' => 300,
                                'minWidth'  => 300,
                                'minHeight' => 300
                            ],
                        ]
                    ],
                    'image_min_max_size' => [
                        'rules' => [
                            'imageSize' => [
                                'maxWidth'  => 300,
                                'maxHeight' => 300,
                                'minWidth'  => 290,
                                'minHeight' => 290
                            ],
                        ]
                    ],
                    'image_min_size' => [
                        'rules' => ['imageSize' => ['minWidth' => 300, 'minHeight' => 300]]
                    ],
                    'image_max_size' => [
                        'rules' => ['imageSize' => ['maxWidth' => 300, 'maxHeight' => 300]]
                    ],
                    'image_only_maxwidth' => [
                        'rules' => ['imageSize' => ['maxWidth' => 300]]
                    ],
                    'image_only_maxheight' => [
                        'rules' => ['imageSize' => ['maxHeight' => 300]]
                    ],
                    'image_only_minwidth' => [
                        'rules' => ['imageSize' => ['minWidth' => 300]]
                    ],
                    'image_only_minheight' => [
                        'rules' => ['imageSize' => ['minHeight' => 300]]
                    ],
                ]
            ]
        ];
    }
}
