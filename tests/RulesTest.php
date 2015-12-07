<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use Yii;
use tests\data\News;

class RulesTest extends \PHPUnit_Framework_TestCase
{
    public function testBaseFileRules()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_path');
        $this->assertContains('Min. size of image: 300x300px', $rules);
        $this->assertContains('Max. file size: 1', $rules);
        $this->assertContains('File types: JPG, JPEG, PNG', $rules);
    }

    public function testImageWithStrictSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_strict_size');
        $this->assertContains('Image size: 300x300px', $rules);
    }

    public function testImageWithMinAndMaxSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_min_max_size');
        $this->assertContains('Min. size of image: 290x290px', $rules);
        $this->assertContains('Max. size of image: 300x300px', $rules);
    }

    public function testImageWithMinSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_min_size');
        $this->assertContains('Min. size of image: 300x300px', $rules);
    }

    public function testImageWithMaxSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_max_size');
        $this->assertContains('Max. size of image: 300x300px', $rules);
    }

    public function testImageWithOnlyMaxWidth()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_maxwidth');
        $this->assertContains('Max. width 300px', $rules);
    }

    public function testImageWithOnlyMaxHeight()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_maxheight');
        $this->assertContains('Max. height 300px', $rules);
    }

    public function testImageWithOnlyMinWidth()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_minwidth');
        $this->assertContains('Min. width 300px', $rules);
    }

    public function testImageWithOnlyMinHeight()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_minheight');
        $this->assertContains('Min. height 300px', $rules);
    }
}
