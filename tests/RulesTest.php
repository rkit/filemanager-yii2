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
    public function testRules()
    {
        $model = new News();

        $rules = $model->getFileRules('image_path');
        $this->assertArrayHasKey('imageSize', $rules);
        $this->assertArrayHasKey('mimeTypes', $rules);
        $this->assertArrayHasKey('extensions', $rules);
        $this->assertArrayHasKey('maxSize', $rules);
        $this->assertArrayHasKey('tooBig', $rules);
    }

    public function testRulesWithOnlyCoreValidators()
    {
        $model = new News();

        $rules = $model->getFileRules('image_path', true);
        $this->assertArrayNotHasKey('imageSize', $rules);
        $this->assertArrayHasKey('mimeTypes', $rules);
        $this->assertArrayHasKey('extensions', $rules);
        $this->assertArrayHasKey('maxSize', $rules);
        $this->assertArrayHasKey('tooBig', $rules);
    }

    public function testDescription()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_path');
        $this->assertContains('Min. size of image: 300x300px', $rules);
        $this->assertContains('Max. file size: 1', $rules);
        $this->assertContains('File types: JPG, JPEG, PNG', $rules);
    }

    public function testDescriptionImageWithStrictSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_strict_size');
        $this->assertContains('Image size: 300x300px', $rules);
    }

    public function testDescriptionImageWithMinAndMaxSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_min_max_size');
        $this->assertContains('Min. size of image: 290x290px', $rules);
        $this->assertContains('Max. size of image: 300x300px', $rules);
    }

    public function testDescriptionImageWithMinSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_min_size');
        $this->assertContains('Min. size of image: 300x300px', $rules);
    }

    public function testDescriptionImageWithMaxSize()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_max_size');
        $this->assertContains('Max. size of image: 300x300px', $rules);
    }

    public function testDescriptionImageWithOnlyMaxWidth()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_maxwidth');
        $this->assertContains('Max. width 300px', $rules);
    }

    public function testDescriptionImageWithOnlyMaxHeight()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_maxheight');
        $this->assertContains('Max. height 300px', $rules);
    }

    public function testDescriptionImageWithOnlyMinWidth()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_minwidth');
        $this->assertContains('Min. width 300px', $rules);
    }

    public function testDescriptionImageWithOnlyMinHeight()
    {
        $model = new News();

        $rules = $model->getFileRulesDescription('image_only_minheight');
        $this->assertContains('Min. height 300px', $rules);
    }
}
